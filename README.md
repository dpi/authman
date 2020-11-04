Authman

OAuth 2 authorization API and key manager.

https://www.drupal.org/project/authman

# Usage

Note: Authman instance config and associated client credentials, and optionally
(usually) access token must be setup beforehand.

```php
/** @var \Drupal\authman\AuthmanInstance\AuthmanOauthFactoryInterface $oauthFactory */
$oauthFactory = \Drupal::service('authman.oauth');
try {
  $authmanInstance = $oauthFactory->get('ID_OF_authman_auth_CONFIG');
  $response = $authmanInstance
    ->authenticatedRequest('GET', 'https://sample.api.example.com/v2/data?p=2');
  $successResponse = (string) $response->getBody();
}
catch (\Exception $e) {
  // Plugin or configuration failure.
}
catch (\GuzzleHttp\Exception\GuzzleException $e) {
  $failureResponse = (string) $e->getResponse()->getBody();
}
```

# License

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
