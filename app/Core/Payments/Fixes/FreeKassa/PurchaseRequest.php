<?php
/**
 * FreeKassa driver for Omnipay PHP payment library
 *
 * @link      https://github.com/hiqdev/omnipay-freekassa
 * @package   omnipay-freekassa
 * @license   MIT
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace Omnipay\FreeKassa\Message;

class PurchaseRequest extends AbstractRequest
{
    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    public function getClient(): string
    {
        return (string) $this->getParameter('client');
    }

    public function setClient($value)
    {
        return $this->setParameter('client', $value);
    }

    public function getData()
    {
        $this->validate(
            'purse', 'secretKey',
            'amount', 'currency', 'transactionId'
        );

        return array_filter([
            'm' => $this->getPurse(),
            'oa' => $this->getAmount(),
            'o' => $this->getTransactionId(),
            's' => $this->calculateSignature(),
            'lang' => $this->getLanguage(),
            'us_client' => $this->getClient(),
            'currency' => strtoupper($this->getCurrency() ?? 'RUB'),
            'us_system' => 'freekassa',
            'us_currency' => strtoupper($this->getCurrency() ?? 'RUB'),
        ]);
    }

    /**
     * @return string
     */
    public function calculateSignature(): string
    {
        return md5(implode(':', [
            $this->getPurse(),
            $this->getAmount(),
            $this->getSecretKey(),
            strtoupper($this->getCurrency() ?? 'RUB'),
            $this->getTransactionId(),
        ]));
    }

    public function sendData($data)
    {
        return $this->response = new PurchaseResponse($this, $data);
    }
}
