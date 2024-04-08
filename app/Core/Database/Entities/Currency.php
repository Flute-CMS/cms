<?php

namespace Flute\Core\Database\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Relation\Pivoted\PivotedCollection;

/**
 * @Entity()
 * @Table(
 *     indexes={
 *         @Index(columns={"code"}, unique=true)
 *     }
 * )
 */
class Currency
{
    /** @Column(type="primary") */
    public $id;

    /** @Column(type="string") */
    public $code;

    /** @Column(type="float") */
    public $minimum_value;

    /** @Column(type="float") */
    public $exchange_rate;

    /**
     * @ManyToMany(target="PaymentGateway", though="CurrencyPaymentGateway")
     */
    public $paymentGateways;

    public function __construct()
    {
        $this->paymentGateways = new PivotedCollection();
    }

    public function addPayment(PaymentGateway $paymentGateway): void
    {
        if (!$this->paymentGateways->contains($paymentGateway)) {
            $this->paymentGateways->add($paymentGateway);
        }
    }

    public function clearPayments(): void
    {
        $this->paymentGateways->clear();
    }

    public function hasPayment(PaymentGateway $paymentGateway): bool
    {
        return $this->paymentGateways->contains($paymentGateway);
    }

    public function hasPaymentByKey(string $gateway): bool
    {
        foreach( $this->paymentGateways as $key => $val ) {
            if( $val->name === $gateway ) {
                return true;
            }
        }

        return false;
    }

    public function removePayment(PaymentGateway $paymentGateway): void
    {
        if ($this->paymentGateways->contains($paymentGateway)) {
            $this->paymentGateways->removeElement($paymentGateway);
        }
    }
}
