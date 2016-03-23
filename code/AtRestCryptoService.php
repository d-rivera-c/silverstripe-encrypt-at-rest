<?php


use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\CannotPerformOperationException;
use Defuse\Crypto\Exception\CryptoTestFailedException;
use Defuse\Crypto\Exception\InvalidCiphertextException;
use Defuse\Crypto\Exception\InvalidInput;
use Defuse\Crypto\File;
use Defuse\Crypto\Key;
use Ex\CryptoException;

class AtRestCryptoService {
    /**
     * @param string $raw
     * @param null|Key $key
     * @return string
     * @throws CannotPerformOperationException
     * @throws CryptoTestFailedException
     */
    public function encrypt($raw, $key = null) {
        $key = $this->getKey($key);
        return Crypto::Encrypt($raw, $key);
    }

    /**
     * @param string $ciphertext
     * @param null|Key $key
     * @return string
     * @throws CannotPerformOperationException
     * @throws InvalidCiphertextException
     */
    public function decrypt($ciphertext, $key = null) {
        $key = $this->getKey($key);
        return Crypto::Decrypt($ciphertext, $key);
    }

    /**
     * @param \File $file
     * @param null|Key $key
     * @return bool|\File
     * @throws InvalidInput
     * @throws CryptoException
     */
    public function encryptFile($file, $key = null) {
        $key = $this->getKey($key);
        $encryptedFilename = str_replace('.txt', '.enc', $file->getFullPath());
        try{
            File::encryptFile($file->getFullPath(), $encryptedFilename, $key);
            unlink($file->getFullPath());
            $file->Filename = str_replace('.txt', '.enc', $file->Filename);
            $file->write();
            return $file;
        } catch (Exception $e) {
            SS_Log::log(sprintf('Encryption exception while parsing "%s": %s', $file, $e->getMessage()), SS_Log::ERR);
            return false;
        }

    }

    /**
     * @param \File $file
     * @param null|Key $key
     * @return bool|\File
     * @throws InvalidInput
     * @throws CryptoException
     */
    public function decryptFile($file, $key = null) {
        $key = $this->getKey($key);
        $decryptedFilename = str_replace('.enc', '.txt', $file->getFullPath());
        try{
            File::encryptFile($file->getFullPath(), $decryptedFilename, $key);
            unlink($file->getFullPath());
            $file->Filename = str_replace('.enc', '.txt', $file->Filename);
            $file->write();
            return $file;
        } catch (Exception $e) {
            SS_Log::log(sprintf('Decryption exception while parsing "%s": %s', $file, $e->getMessage()), SS_Log::ERR);
            return false;
        }
    }

    public function getKey($rawKey) {
        // If this is already a \Defuse\Crypto\Key object, just return it
        if($rawKey instanceof Key) {
            return $rawKey;
        }

        if($rawKey === null && defined('ENCRYPT_AT_REST_KEY')) {
            // Retrieve key from _ss_env, if set
            $rawKey = ENCRYPT_AT_REST_KEY;
        }

        if($rawKey === null) {
            throw new \InvalidArgumentException('Can\'t encrypt without a key. Define ENCRYPT_AT_REST_KEY, or pass the $key argument.');
        }

        $key = Key::LoadFromAsciiSafeString($rawKey);

        return $key;
    }
}
