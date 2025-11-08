<?php

namespace App\Services\Banking;

interface BankAdapterInterface
{
    public function setAccount(array $credentials): self;

    public function getBalance(): float;

    /**
     * @return array{accountNumber: string, balance: float, todayDepositAmount: float, todayWithdrawAmount: float, currency: string}
     */
    public function getAccountBalance(): array;
}
