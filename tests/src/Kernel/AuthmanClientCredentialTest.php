<?php

declare(strict_types = 1);

namespace Drupal\Tests\authman\Kernel;

use Drupal\authman\Entity\AuthmanAuthInterface;
use Drupal\authman_test_providers\Plugin\AuthmanOauth\AuthmanTestClientCredentials;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\authman\Traits\AuthmanConfigTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Integration test for client credentials.
 */
final class AuthmanClientCredentialTest extends KernelTestBase {

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
        '{"access_token": "ABCDE12345ABCDE12345ABCDE12345", "expires_in": 3600, "token_type": "Bearer"}'
      ),
      new Response(
        200,
        ['Content-Type' => 'application/json'],
        '{"abc": "123" }'
      ),
    );
  }

  /**
   * Tests getting client credentials.
   */
  public function testClientCredentials(): void {
    $authmanConfig = $this->createAuthmanConfig(
      AuthmanTestClientCredentials::PLUGIN_ID,
      AuthmanAuthInterface::GRANT_CLIENT_CREDENTIALS,
      $this->createClientKey([
        'client_id' => 'testClientId',
        'client_secret' => 'testClientSecret',
      ]),
      $accessTokenKey = $this->createAccessTokenKey(),
    );
    $redirectUri = Url::fromRoute('authman.authorization_code.receive', ['authman_auth' => $authmanConfig->id()]);

    $authmanInstance = $this->authmanInstanceFactory()->get($authmanConfig->id());

    $this->assertCount(0, $this->historyContainer);
    // Making an authenticated request will initiate getting the first access
    // token values.
    $authmanInstance->authenticatedRequest('GET', 'http://example.com/resource/foo/1');
    $this->assertCount(2, $this->historyContainer);

    /** @var \GuzzleHttp\Psr7\Request[] $requests */
    $requests = array_column($this->historyContainer, 'request');
    /** @var \GuzzleHttp\Psr7\Response[] $responses */
    $responses = array_column($this->historyContainer, 'response');
    $this->assertEquals('POST', $requests[0]->getMethod());
    $this->assertEquals('http://example.com/oauth2/token', (string) $requests[0]->getUri());
    $this->assertEquals('client_id=testClientId&client_secret=testClientSecret&redirect_uri=' . urlencode($redirectUri->setAbsolute()->toString()) . '&grant_type=client_credentials', (string) $requests[0]->getBody());
    $this->assertEquals('{"access_token": "ABCDE12345ABCDE12345ABCDE12345", "expires_in": 3600, "token_type": "Bearer"}', (string) $responses[0]->getBody());

    $this->assertEquals('GET', $requests[1]->getMethod());
    $this->assertEquals('http://example.com/resource/foo/1', (string) $requests[1]->getUri());
    $this->assertEquals('{"abc": "123" }', (string) $responses[1]->getBody());

    // Reload auth token.
    $accessTokenKey = $accessTokenKey::load($accessTokenKey->id());
    $actualValues = $accessTokenKey->getKeyValues(TRUE);
    // Ignore 'expires' for now since its hard to mock: AccessToken uses time().
    unset($actualValues['expires']);
    $this->assertEquals([
      'access_token' => 'ABCDE12345ABCDE12345ABCDE12345',
      'refresh_token' => NULL,
      'token_type' => 'Bearer',
    ], $actualValues);
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
