<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\Kernel;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\authman\Exception\AuthmanTokenRenewalException;
use Drupal\authman\Token\AuthmanAccessToken;
use Drupal\authman_test_providers\Plugin\AuthmanOauth\AuthmanTestAuthorizationCode;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\authman\Traits\AuthmanConfigTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Integration test for authorization code.
 */
final class AuthmanAuthorizationCodeTest extends KernelTestBase {

  use AuthmanConfigTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'key',
    'user',
    'authman',
    'authman_test_providers',
    'authman_test_time',
  ];

  /**
   * Captures HTTP requests.
   *
   * @var array
   *
   * @see \GuzzleHttp\Middleware::history
   */
  protected $historyContainer = [];

  /**
   * The current time.
   *
   * @var \DateTimeInterface
   */
  protected $currentTime;

  /**
   * A mock Guzzle handler.
   *
   * @var \GuzzleHttp\Handler\MockHandler
   */
  protected $mockHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->mockHandler = new MockHandler();

    parent::setUp();

    /** @var \Drupal\authman_test_time\TimeMachine $timeMachine */
    $timeMachine = \Drupal::service('datetime.time');
    $this->currentTime = new \DateTime('18th October 2014 4:00:00pm Asia/Singapore');
    $timeMachine->setTime($this->currentTime);

    $this->mockHandler->append(
      new Response(
        200,
        ['Content-Type' => 'application/json'],
        '{
          "access_token": "NEW_ACCESS_TOKEN",
          "expires_in": 3599,
          "token_type": "Bearer"
        }'
      ),
      new Response(
        200,
        ['Content-Type' => 'application/json'],
        '{"abc": "123" }'
      ),
    );
  }

  /**
   * Tests exception is thrown if initial token has not been obtained.
   */
  public function testNoAccessToken(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      $this->createClientKey([
        'client_id' => 'testClientId',
        'client_secret' => 'testClientSecret',
      ]),
      $this->createAccessTokenKey(),
    );
    $authmanInstance = $this->authmanInstanceFactory()->get($authmanConfig->id());

    $this->expectException(AuthmanTokenRenewalException::class);
    $this->expectExceptionMessage('Cant refresh an authorization code grant when the initial code has not been fetched.');
    $authmanInstance->authenticatedRequest('GET', 'http://example.com/resource/foo/1');
  }

  /**
   * Tests token refresh flow for expired access tokens.
   */
  public function testAutoRenew(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      $this->createClientKey([
        'client_id' => 'testClientId',
        'client_secret' => 'testClientSecret',
      ]),
      $accessTokenKey = $this->createAccessTokenKey(),
    );
    $redirectUri = Url::fromRoute('authman.authorization_code.receive', ['authman_auth' => $authmanConfig->id()]);

    // Create a access token that needs to be refreshed.
    $accessToken = new AccessToken([
      'access_token' => 'EXISTING_ACCESS_TOKEN',
      'expires' => (new \DateTime('1 day ago'))->getTimestamp(),
      'refresh_token' => 'A_REFRESH_TOKEN',
      'resource_owner_id' => NULL,
      'values' => [],
    ]);
    $token = new AuthmanAccessToken($accessTokenKey->id(), $accessToken);
    $token->saveToKey();

    $authmanInstance = $this->authmanInstanceFactory()->get($authmanConfig->id());

    $this->assertCount(0, $this->historyContainer);
    $authmanInstance->authenticatedRequest('GET', 'http://example.com/resource/foo/1');
    $this->assertCount(2, $this->historyContainer);

    // Refresh token flow.
    /** @var \GuzzleHttp\Psr7\Request[] $requests */
    $requests = array_column($this->historyContainer, 'request');
    /** @var \GuzzleHttp\Psr7\Response[] $responses */
    $responses = array_column($this->historyContainer, 'response');

    $this->assertEquals('POST', $requests[0]->getMethod());
    $this->assertEquals('http://example.com/oauth2/token', (string) $requests[0]->getUri());
    $this->assertEquals('client_id=testClientId&client_secret=testClientSecret&redirect_uri=' . urlencode($redirectUri->setAbsolute()->toString()) . '&grant_type=refresh_token&refresh_token=' . urlencode('A_REFRESH_TOKEN'), (string) $requests[0]->getBody());
    $this->assertEquals('application/json', $responses[0]->getHeader('Content-Type')[0]);

    $this->assertEquals('GET', $requests[1]->getMethod());
    $this->assertEquals('http://example.com/resource/foo/1', (string) $requests[1]->getUri());
    $this->assertEquals('{"abc": "123" }', (string) $responses[1]->getBody());

    // Reloading instance will reload the token.
    $authmanInstance = $this->authmanInstanceFactory()->get($authmanConfig->id());
    $values = $authmanInstance->getToken()->jsonSerialize();
    // Ignore 'expires' for now since its hard to mock: AccessToken uses time().
    unset($values['expires']);
    $this->assertEquals([
      'access_token' => 'NEW_ACCESS_TOKEN',
      'refresh_token' => 'A_REFRESH_TOKEN',
    ], $values);
  }

  /**
   * Tests when an access token has not expired yet.
   */
  public function testTokenValid(): void {
    $this->mockHandler->reset();
    $this->mockHandler->append(new Response(
      200,
      ['Content-Type' => 'application/json'],
      '{"abc": "123" }'
    ));

    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestAuthorizationCode::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_AUTHORIZATION_CODE,
      $this->createClientKey([
        'client_id' => 'testClientId',
        'client_secret' => 'testClientSecret',
      ]),
      $accessTokenKey = $this->createAccessTokenKey(),
    );

    // Create a access token which doesnt need to be refreshed.
    $accessToken = new AccessToken([
      'access_token' => 'EXISTING_ACCESS_TOKEN',
      'expires' => (new \DateTime('+1 day'))->getTimestamp(),
      'refresh_token' => 'A_REFRESH_TOKEN',
      'resource_owner_id' => NULL,
      'values' => [],
    ]);
    $token = new AuthmanAccessToken($accessTokenKey->id(), $accessToken);
    $token->saveToKey();

    $authmanInstance = $this->authmanInstanceFactory()->get($authmanConfig->id());

    $this->assertCount(0, $this->historyContainer);
    $authmanInstance->authenticatedRequest('GET', 'http://example.com/resource/foo/1');
    $this->assertCount(1, $this->historyContainer);

    /** @var \GuzzleHttp\Psr7\Request[] $requests */
    $requests = array_column($this->historyContainer, 'request');
    /** @var \GuzzleHttp\Psr7\Response[] $responses */
    $responses = array_column($this->historyContainer, 'response');
    $this->assertEquals('GET', $requests[0]->getMethod());
    $this->assertEquals('http://example.com/resource/foo/1', (string) $requests[0]->getUri());
    $this->assertEquals('{"abc": "123" }', (string) $responses[0]->getBody());
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $handlerStack = HandlerStack::create($this->mockHandler);
    $handlerStack->push(Middleware::history($this->historyContainer));

    $httpClient = new Client(['handler' => $handlerStack]);
    $container->set('http_client', $httpClient);
  }

}
