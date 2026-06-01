<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;
use Crystal\Finance\Core\Exception\UnbalancedLedgerTransaction;
use Crystal\Finance\Core\Money\Money;

final readonly class LedgerValidator
{
    public function assertValid(LedgerTransaction $transaction): void
    {
        $entries = $transaction->entries();

        if ($entries === []) {
            throw InvalidLedgerTransaction::empty();
        }

        $debits = [];
        $credits = [];

        foreach ($entries as $entry) {
            $assetId = $entry->amount()->asset()->id()->value();

            $debits[$assetId] ??= Money::zero($entry->amount()->asset());
            $credits[$assetId] ??= Money::zero($entry->amount()->asset());

            if ($entry->direction() === LedgerDirection::Debit) {
                $debits[$assetId] = $debits[$assetId]->plus($entry->amount());
            } else {
                $credits[$assetId] = $credits[$assetId]->plus($entry->amount());
            }
        }

        foreach ($debits as $assetId => $debitAmount) {
            $creditAmount = $credits[$assetId] ?? Money::zero($debitAmount->asset());

            if ($debitAmount->compareTo($creditAmount) !== 0) {
                throw UnbalancedLedgerTransaction::forAsset(
                    $assetId,
                    $debitAmount->toDecimalString(),
                    $creditAmount->toDecimalString(),
                );
            }
        }

    }
}
