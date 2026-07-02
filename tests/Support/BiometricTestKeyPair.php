<?php

namespace Tests\Support;

use App\Modules\CustomerAuth\Support\BiometricSignatureVerifier;

class BiometricTestKeyPair
{
    private const PRIVATE_KEY = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDC9QL5C7y5S5pR
EKFg818sJ15C6cck+XepDfkjFWfJVxPLBvcjl1un3xjTKTyTwSgYvq6BkEDN27BT
zi7lJwJ9bhjTPyBteJd5BdchzoXwh8xYgY7/VChw/WENRn3R7npA130OUAsSUOib
p88earHBqXEW1fJxmnj83gZut5e5uE97PXBYCyyJ1N4xLbCWuxNASEjNcTyCcsUP
gIIEMXU0Vej1tTSfcjbqeWoaip5c6Rv7DsSBpm67g7+FvV/dvbV/V4eQ5FIS1Ztr
XtBpRU18ev1TnzOoqDYJqS07eJ0NvJyAmGpm2KZxFdqyOHw5IVqjFElgeZ+WGE5q
V0x3z7ibAgMBAAECggEACri3E04vcKX1PyG3K6HxaSK4dDKaG/DyQTmpAA0Zz5X6
hdmvYWWItLM4EMjS8YdCYWtUeoCd577+CVCB/g0xUweT1Zjs+3LnKvsvaRxhUP3k
NuEtJY08dMuieOdDB8wMgiC3r7/8KTjfifaRFT77NpzPFi6UQ5DMuDoCSDglinY9
RzrihyI6oBPhhVq/hXhghDoASE5+WKpFbro1cUTUJ64Y63DM2ohJrlKnmhGtLNqs
9u+1eeSO56xuKCEy6ORA86WI1lbyxwldGzuHAgnyUG9jec/IWMvWomFUsE/NRb+u
AvnYgKz9TS0GwLHmfFLe3IcJ7eQc9aMkEmXG/FYbWQKBgQDupcdM2oScK/AuItTL
N8jFbTrON3VMGZYIaotwVlEmlHreJnkZrulZIoHmK/zR5aSw0MCpSHCYUd2UHXaK
xRHg0v6UYLBs1z0Nc8dy6Uu3YTN1MHV91g4mbmkaM89FmFilS7KRXIfk2+8szBpN
jrDoTChXLDIzu4QCE0ZEtf5qwwKBgQDRIfjBGiZZRh8yWorKb3fKu7ThqZeZk81+
h5m5xUvlzAeO9MIIcs2tnZwbREchmoswJ+zWplHxn7zK3/JscNF8uQz/XenHwcJt
gw8RPuRb8TpNlV7ybasc4pnuzpEXU+RU9y8Re6qS56bL/NMOcDGk2SwNggpxF5kK
0zZLEpUtSQKBgEEo1n/vciHKBWwzanKKKrFtH49KqSY8HjDyFlx31PR5ugqJ6qrO
jTwfJeSYwhD1aMA2X0RZWRTd1Wgpm0JMiJMgSQ5uT/2Hz02q/RwtAtVxHsGyl4Hi
Pj7UCrJyzvcrn5iSUJFL+HbObkGHAO+INFlY4fLbSGHDo0mFxbvhDTRNAoGBALlG
vIfvL9xViFvm5SD1Yg6E/3oQ8pMH471eSu3PAi+i82tOIfy0IM3YXS227sGxlfBi
3qtIDD7hQrQFdVNnG+DAXOh8fhoP6b11p8qiilN+QiXr2IM0b60WgEOWU+pz000G
9HcrtXGSsy3zXdRjx0eBc0rAU5nfyyFQ/7/AxdKpAoGBAIXnntPfrX51xhoRW+kI
MT5WW1CXmHbP8CA6xwmnykD3stCRe8RPqlDQAFxI7j04xq/5/ZKVP7NUXcd4Asb9
FHK3PuQHRD5JqDKrNTC411eDkUWkjqB6Ipg+wCsvjL3EJLNK8sF/2x9KaHyWaEhf
Oaj97EBoA98HCaXv+xWz1c4r
-----END PRIVATE KEY-----
PEM;

    private const PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwvUC+Qu8uUuaURChYPNf
LCdeQunHJPl3qQ35IxVnyVcTywb3I5dbp98Y0yk8k8EoGL6ugZBAzduwU84u5ScC
fW4Y0z8gbXiXeQXXIc6F8IfMWIGO/1QocP1hDUZ90e56QNd9DlALElDom6fPHmqx
walxFtXycZp4/N4GbreXubhPez1wWAssidTeMS2wlrsTQEhIzXE8gnLFD4CCBDF1
NFXo9bU0n3I26nlqGoqeXOkb+w7EgaZuu4O/hb1f3b21f1eHkORSEtWba17QaUVN
fHr9U58zqKg2CaktO3idDbycgJhqZtimcRXasjh8OSFaoxRJYHmflhhOaldMd8+4
mwIDAQAB
-----END PUBLIC KEY-----
PEM;

    public const ALGORITHM = 'RS256';

    public static function publicKey(): string
    {
        return self::PUBLIC_KEY;
    }

    public static function sign(string $nonce): string
    {
        $privateKey = openssl_pkey_get_private(self::PRIVATE_KEY);
        if ($privateKey === false) {
            throw new \RuntimeException('Failed to load biometric test private key');
        }

        $signed = openssl_sign($nonce, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if ($signed !== true) {
            throw new \RuntimeException('Failed to sign biometric test nonce');
        }

        return BiometricSignatureVerifier::base64UrlEncode($signature);
    }

    public static function signNonceFromResponse(string $nonceBase64Url): string
    {
        $nonce = BiometricSignatureVerifier::base64UrlDecode($nonceBase64Url);
        if ($nonce === false) {
            throw new \InvalidArgumentException('Invalid nonce encoding');
        }

        return self::sign($nonce);
    }

    public static function reset(): void
    {
        // Static keys; nothing to reset.
    }
}
