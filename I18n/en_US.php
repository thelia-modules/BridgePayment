<?php

return [
    'CREA' => 'Payment created',
    'ACTC' => 'Payment created',
    'PDNG' => 'Waiting',
    'ACSC' => 'Paid',
    'RJCT' => 'Cancel',
    'VALID' => 'Valid',
    'EXPIRED' => 'Expired',
    'REVOKED' => 'Revoked',
    'COMPLETED' => 'Completed',
    'AC01' => '(IncorrectAccountNumber): the account number is either invalid or does not exist',
    'AC04' => '(ClosedAccountNumber): the account is closed and cannot be used',
    'AC06' => '(BlockedAccount): the account is blocked and cannot be used',
    'AG01' => '(Transaction forbidden): Transaction forbidden on this type of account',
    'AM18' => '(InvalidNumberOfTransactions): the number of transactions exceeds the ASPSP acceptance limit',
    'CH03' => '(RequestedExecutionDateOrRequestedCollectionDateTooFarInFuture): The requested execution date is too far in the future',
    'CUST' => '(RequestedByCustomer): The reject is due to the debtor: refusal or lack of liquidity',
    'DS02' => '(OrderCancelled): An authorized user has cancelled the order',
    'FF01' => '(InvalidFileFormat): The reject is due to the original Payment Request which is invalid (syntax, structure or values)',
    'FRAD' => '(FraudulentOriginated): the Payment Request is considered as fraudulent',
    'MS03' => '(NotSpecifiedReasonAgentGenerated): No reason specified by the ASPSP (usually the bank)',
    'NOAS' => '(NoAnswerFromCustomer): The PSU (the user) has neither accepted nor rejected the Payment Request and a timeout has occurred',
    'RR01' => '(MissingDebtorAccountOrIdentification): The Debtor account and/or Identification are missing or inconsistent',
    'RR03' => '(MissingCreditorNameOrAddress): Specification of the creditor’s name and/or address needed for regulatory requirements is insufficient or missing',
    'RR04' => '(RegulatoryReason): Reject from regulatory reason',
    'RR12' => '(InvalidPartyID): Invalid or missing identification required within a particular country or payment type',
];