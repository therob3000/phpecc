<?php

namespace Mdanter\Ecc\Serializer\PrivateKey;

use PHPASN1\ASN_ObjectIdentifier;
use PHPASN1\ASN_Sequence;
use PHPASN1\ASN_Integer;
use PHPASN1\ASN_BitString;
use Mdanter\Ecc\PrivateKeyInterface;
use Mdanter\Ecc\MathAdapterInterface;
use Mdanter\Ecc\Math\MathAdapterFactory;
use PHPASN1\ASN_Object;
use Mdanter\Ecc\Serializer\Util\CurveOidMapper;
use PHPASN1\ASN_OctetString;
use Mdanter\Ecc\Serializer\PublicKey\PemPublicKeySerializer;
use PHPASN1\ASN_UnknownConstructedObject;
use Mdanter\Ecc\Util\NumberSize;
use Mdanter\Ecc\Serializer\Util\OctetStringConverter;
use Mdanter\Ecc\Serializer\Util\ASN\ASNContext;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\Der\Parser;

/**
 * PEM Private key formatter
 *
 * @link https://tools.ietf.org/html/rfc5915
 */
class DerPrivateKeySerializer implements PrivateKeySerializerInterface
{

    const VERSION = 1;

    private $adapter;

    private $pubKeySerializer;

    public function __construct(MathAdapterInterface $adapter = null, PemPublicKeySerializer $pubKeySerializer = null)
    {
        $this->adapter = $adapter ?: MathAdapterFactory::getAdapter();
        $this->pubKeySerializer = $pubKeySerializer ?: new DerPublicKeySerializer($this->adapter);
    }

    public function serialize(PrivateKeyInterface $key)
    {
        $privateKeyInfo = new ASN_Sequence(
            new ASN_Integer(self::VERSION),
            new ASN_OctetString($this->formatKey($key)),
            new ASNContext(160, CurveOidMapper::getCurveOid($key->getPoint()->getCurve())),
            new ASNContext(161, $this->encodePubKey($key))
        );

        return $privateKeyInfo->getBinary();
    }

    private function encodePubKey(PrivateKeyInterface $key)
    {
        return new ASN_BitString(
            $this->pubKeySerializer->getUncompressedKey($key->getPublicKey())
        );
    }

    private function formatKey(PrivateKeyInterface $key)
    {
        return $this->adapter->decHex($key->getSecret());
    }

    public function parse($data)
    {
        $asnObject = ASN_Object::fromBinary($data);

        if (! ($asnObject instanceof ASN_Sequence) || $asnObject->getNumberofChildren() !== 4) {
            throw new \RuntimeException('Invalid data.');
        }

        $children = $asnObject->getChildren();

        $version = $children[0];

        if ($version->getContent() != 1) {
            throw new \RuntimeException('Invalid data: only version 1 (RFC5915) keys are supported.');
        }

        $key = $this->adapter->hexDec($children[1]->getContent());
        $oid = $children[2]->getFirstChild();

        $generator = CurveOidMapper::getGeneratorFromOid($oid);

        return $generator->getPrivateKeyFrom($key);
    }
}
