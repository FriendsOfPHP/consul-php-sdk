<?php

namespace Consul\Tests\Services;

use Consul\Client;
use Consul\Exception\ClientException;
use Consul\Services\ACL;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ACLTest extends TestCase
{
    private const PREFIX = 'consul-php-sdk-test-';

    private ACL $acl;

    protected function setUp(): void
    {
        $this->acl = new ACL();
        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
    }

    public function testBootstrapIsRejectedWhenAlreadyBootstrapped(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessageMatches('/403/');

        $this->acl->bootstrap();
    }

    public function testBootstrap(): void
    {
        $acl = $this->createMockedACL('PUT', '/v1/acl/bootstrap', '', '{"BootstrapSecret":"2b778dd9-f5f1-6f29-b4b4-9a5fa948757a"}', '{"SecretID":"2b778dd9-f5f1-6f29-b4b4-9a5fa948757a"}');

        $response = $acl->bootstrap(['BootstrapSecret' => '2b778dd9-f5f1-6f29-b4b4-9a5fa948757a']);

        self::assertSame('2b778dd9-f5f1-6f29-b4b4-9a5fa948757a', $response->json()['SecretID']);
    }

    public function testBootstrapWithoutSecret(): void
    {
        $acl = $this->createMockedACL('PUT', '/v1/acl/bootstrap', '', '{}', '{}');

        self::assertTrue($acl->bootstrap()->isSuccessful());
    }

    public function testReplication(): void
    {
        $json = $this->acl->replication(['dc' => 'dc1', 'consistent' => true, 'unknown' => 'foo'])->json();

        self::assertFalse($json['Enabled']);
        self::assertArrayHasKey('ReplicationType', $json);
    }

    public function testCreateReadUpdateDeleteToken(): void
    {
        $token = $this->acl->createToken(['Description' => self::PREFIX.'token'])->json();
        self::assertSame(self::PREFIX.'token', $token['Description']);

        $read = $this->acl->readToken($token['AccessorID'], ['expanded' => true])->json();
        self::assertSame($token['SecretID'], $read['SecretID']);
        self::assertArrayHasKey('ExpandedPolicies', $read);

        $updated = $this->acl->updateToken($token['AccessorID'], ['Description' => self::PREFIX.'token updated'])->json();
        self::assertSame(self::PREFIX.'token updated', $updated['Description']);

        $this->acl->deleteToken($token['AccessorID']);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessageMatches('/403/');

        $this->acl->readToken($token['AccessorID']);
    }

    public function testCreateTokenWithoutPayload(): void
    {
        $token = $this->acl->createToken()->json();

        $this->acl->deleteToken($token['AccessorID']);

        self::assertNotEmpty($token['SecretID']);
    }

    public function testReadSelfToken(): void
    {
        $json = $this->acl->readSelfToken()->json();

        self::assertSame('root', $json['SecretID']);
    }

    public function testCloneToken(): void
    {
        $token = $this->acl->createToken(['Description' => self::PREFIX.'token'])->json();
        $clone = $this->acl->cloneToken($token['AccessorID'], ['Description' => self::PREFIX.'clone'])->json();
        $cloneWithoutPayload = $this->acl->cloneToken($token['AccessorID'])->json();

        self::assertNotSame($token['AccessorID'], $clone['AccessorID']);
        self::assertSame(self::PREFIX.'clone', $clone['Description']);
        self::assertSame(self::PREFIX.'token', $cloneWithoutPayload['Description']);
    }

    public function testListTokens(): void
    {
        $policy = $this->acl->createPolicy(['Name' => self::PREFIX.'policy'])->json();
        $token = $this->acl->createToken(['Description' => self::PREFIX.'token', 'Policies' => [['ID' => $policy['ID']]]])->json();

        $all = array_column($this->acl->listTokens()->json(), 'AccessorID');
        self::assertContains($token['AccessorID'], $all);

        $filtered = array_column($this->acl->listTokens(['policy' => $policy['ID']])->json(), 'AccessorID');
        self::assertSame([$token['AccessorID']], $filtered);
    }

    public function testCreateReadUpdateDeletePolicy(): void
    {
        $policy = $this->acl->createPolicy(['Name' => self::PREFIX.'policy', 'Rules' => 'key_prefix "" { policy = "read" }'])->json();
        self::assertSame(self::PREFIX.'policy', $policy['Name']);

        self::assertSame($policy['ID'], $this->acl->readPolicy($policy['ID'])->json()['ID']);
        self::assertSame($policy['ID'], $this->acl->readPolicyByName(self::PREFIX.'policy')->json()['ID']);

        $updated = $this->acl->updatePolicy($policy['ID'], ['Name' => self::PREFIX.'policy', 'Description' => 'updated'])->json();
        self::assertSame('updated', $updated['Description']);

        self::assertContains($policy['ID'], array_column($this->acl->listPolicies()->json(), 'ID'));

        $this->acl->deletePolicy($policy['ID']);

        self::assertNotContains($policy['ID'], array_column($this->acl->listPolicies()->json(), 'ID'));
    }

    public function testCreateReadUpdateDeleteRole(): void
    {
        $policy = $this->acl->createPolicy(['Name' => self::PREFIX.'policy'])->json();
        $role = $this->acl->createRole(['Name' => self::PREFIX.'role', 'Policies' => [['ID' => $policy['ID']]]])->json();
        self::assertSame(self::PREFIX.'role', $role['Name']);

        self::assertSame($role['ID'], $this->acl->readRole($role['ID'])->json()['ID']);
        self::assertSame($role['ID'], $this->acl->readRoleByName(self::PREFIX.'role')->json()['ID']);

        $updated = $this->acl->updateRole($role['ID'], ['Name' => self::PREFIX.'role', 'Description' => 'updated', 'Policies' => [['ID' => $policy['ID']]]])->json();
        self::assertSame('updated', $updated['Description']);

        self::assertContains($role['ID'], array_column($this->acl->listRoles()->json(), 'ID'));
        self::assertSame([$role['ID']], array_column($this->acl->listRoles(['policy' => $policy['ID']])->json(), 'ID'));

        $this->acl->deleteRole($role['ID']);

        self::assertNotContains($role['ID'], array_column($this->acl->listRoles()->json(), 'ID'));
    }

    public function testCreateReadUpdateDeleteAuthMethod(): void
    {
        [, $publicKey] = $this->generateKeyPair();

        $authMethod = $this->acl->createAuthMethod($this->createJwtAuthMethodPayload($publicKey))->json();
        self::assertSame(self::PREFIX.'jwt', $authMethod['Name']);

        self::assertSame('jwt', $this->acl->readAuthMethod(self::PREFIX.'jwt')->json()['Type']);

        $updated = $this->acl->updateAuthMethod(self::PREFIX.'jwt', ['Description' => 'updated'] + $this->createJwtAuthMethodPayload($publicKey))->json();
        self::assertSame('updated', $updated['Description']);

        self::assertContains(self::PREFIX.'jwt', array_column($this->acl->listAuthMethods()->json(), 'Name'));

        $this->acl->deleteAuthMethod(self::PREFIX.'jwt');

        self::assertNotContains(self::PREFIX.'jwt', array_column($this->acl->listAuthMethods()->json(), 'Name'));
    }

    public function testCreateReadUpdateDeleteBindingRule(): void
    {
        [, $publicKey] = $this->generateKeyPair();
        $this->acl->createAuthMethod($this->createJwtAuthMethodPayload($publicKey));

        $rule = $this->acl->createBindingRule(['AuthMethod' => self::PREFIX.'jwt', 'BindType' => 'service', 'BindName' => 'web'])->json();
        self::assertSame('web', $rule['BindName']);

        self::assertSame($rule['ID'], $this->acl->readBindingRule($rule['ID'])->json()['ID']);

        $updated = $this->acl->updateBindingRule($rule['ID'], ['AuthMethod' => self::PREFIX.'jwt', 'BindType' => 'service', 'BindName' => 'api'])->json();
        self::assertSame('api', $updated['BindName']);

        self::assertSame([$rule['ID']], array_column($this->acl->listBindingRules(['authmethod' => self::PREFIX.'jwt'])->json(), 'ID'));

        $this->acl->deleteBindingRule($rule['ID']);

        self::assertSame([], $this->acl->listBindingRules(['authmethod' => self::PREFIX.'jwt'])->json());
    }

    public function testLoginLogout(): void
    {
        [$privateKey, $publicKey] = $this->generateKeyPair();
        $this->acl->createAuthMethod($this->createJwtAuthMethodPayload($publicKey));
        $this->acl->createBindingRule(['AuthMethod' => self::PREFIX.'jwt', 'BindType' => 'service', 'BindName' => 'web']);

        $token = $this->acl->login([
            'AuthMethod' => self::PREFIX.'jwt',
            'BearerToken' => $this->createJwt($privateKey),
            'Meta' => ['source' => 'test'],
        ])->json();

        self::assertSame(self::PREFIX.'jwt', $token['AuthMethod']);
        self::assertSame('web', $token['ServiceIdentities'][0]['ServiceName']);
        self::assertSame($token['AccessorID'], $this->acl->readToken($token['AccessorID'])->json()['AccessorID']);

        self::assertTrue($this->acl->logout($token['SecretID'])->isSuccessful());

        $this->expectException(ClientException::class);

        $this->acl->readToken($token['AccessorID']);
    }

    public function testLogout(): void
    {
        $acl = $this->createMockedACL('POST', '/v1/acl/logout', '', '', '', ['X-Consul-Token: my-secret']);

        self::assertTrue($acl->logout('my-secret')->isSuccessful());
    }

    public function testListTemplatedPolicies(): void
    {
        $json = $this->acl->listTemplatedPolicies()->json();

        self::assertArrayHasKey('builtin/service', $json);
    }

    public function testReadTemplatedPolicy(): void
    {
        $json = $this->acl->readTemplatedPolicy('builtin/service')->json();

        self::assertSame('builtin/service', $json['TemplateName']);
    }

    public function testPreviewTemplatedPolicy(): void
    {
        $json = $this->acl->previewTemplatedPolicy('builtin/service', ['Name' => 'api'])->json();

        self::assertStringContainsString('service "api"', $json['Rules']);
    }

    public function testPreviewTemplatedPolicyWithoutVariables(): void
    {
        $json = $this->acl->previewTemplatedPolicy('builtin/dns')->json();

        self::assertStringContainsString('query_prefix', $json['Rules']);
    }

    public function testQueryOptionsAreFiltered(): void
    {
        $acl = $this->createMockedACL('GET', '/v1/acl/tokens', 'policy=p&role=r&servicename=s&authmethod=a&index=1&wait=1s&stale=1&consistent=1&dc=dc1', '', '[]');

        $acl->listTokens(['policy' => 'p', 'role' => 'r', 'servicename' => 's', 'authmethod' => 'a', 'index' => 1, 'wait' => '1s', 'stale' => true, 'consistent' => true, 'dc' => 'dc1', 'ns' => 'foo']);
    }

    private function createMockedACL(string $method, string $path, string $query, string $body, string $responseBody, array $expectedHeaders = []): ACL
    {
        $callback = static function (string $m, string $url, array $options) use ($method, $path, $query, $body, $responseBody, $expectedHeaders): MockResponse {
            self::assertSame($method, $m);
            self::assertSame('http://127.0.0.1:8500'.$path.('' !== $query ? '?'.$query : ''), $url);
            self::assertSame($body, $options['body'] ?? '');
            foreach ($expectedHeaders as $header) {
                self::assertContains($header, $options['headers']);
            }

            return new MockResponse($responseBody);
        };

        return new ACL(new Client(client: new MockHttpClient($callback, 'http://127.0.0.1:8500')));
    }

    private function createJwtAuthMethodPayload(string $publicKey): array
    {
        return [
            'Name' => self::PREFIX.'jwt',
            'Type' => 'jwt',
            'Config' => [
                'JWTValidationPubKeys' => [$publicKey],
                'BoundAudiences' => ['consul'],
                'ClaimMappings' => ['sub' => 'subject'],
            ],
        ];
    }

    /**
     * @return array{\OpenSSLAsymmetricKey, string}
     */
    private function generateKeyPair(): array
    {
        $key = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($key);

        $details = openssl_pkey_get_details($key);
        self::assertNotFalse($details);

        return [$key, $details['key']];
    }

    private function createJwt(\OpenSSLAsymmetricKey $privateKey): string
    {
        $encode = static fn (array $data): string => rtrim(strtr(base64_encode(json_encode($data, \JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        $payload = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'sub' => 'test',
            'aud' => 'consul',
            'iat' => time() - 10,
            'nbf' => time() - 10,
            'exp' => time() + 300,
        ]);

        openssl_sign($payload, $signature, $privateKey, \OPENSSL_ALGO_SHA256);

        return $payload.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    private function cleanUp(): void
    {
        foreach ($this->acl->listTokens()->json() as $token) {
            if (str_starts_with($token['Description'], self::PREFIX) || str_starts_with($token['AuthMethod'] ?? '', self::PREFIX)) {
                $this->acl->deleteToken($token['AccessorID']);
            }
        }
        foreach ($this->acl->listRoles()->json() as $role) {
            if (str_starts_with($role['Name'], self::PREFIX)) {
                $this->acl->deleteRole($role['ID']);
            }
        }
        foreach ($this->acl->listPolicies()->json() as $policy) {
            if (str_starts_with($policy['Name'], self::PREFIX)) {
                $this->acl->deletePolicy($policy['ID']);
            }
        }
        // Deleting an auth method also deletes its binding rules
        foreach ($this->acl->listAuthMethods()->json() as $authMethod) {
            if (str_starts_with($authMethod['Name'], self::PREFIX)) {
                $this->acl->deleteAuthMethod($authMethod['Name']);
            }
        }
    }
}
