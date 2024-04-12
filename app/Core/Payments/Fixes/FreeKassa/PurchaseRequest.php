<?php
/**
 * FreeKassa driver for Omnipay PHP payment library
 *
 * @link      https://github.com/hiqdev/omnipay-freekassa
 * @package   omnipay-freekassa
 * @license   MIT
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace Flute\Core\Payments\Fixes\FreeKassa;

class PurchaseRequest
{
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
}
