<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;

enum CurrencyCode: string { case USD = 'USD'; case HKD = 'HKD'; case EUR = 'EUR'; }
enum WalletStatus: string { case ACTIVE = 'ACTIVE'; case DISABLED = 'DISABLED'; }
enum CardType: string { case RECHARGE = 'RECHARGE'; case SHARED = 'SHARED'; }
enum CardStatus: string { case UNACTIVE = 'UNACTIVE'; case APPLYING = 'APPLYING'; case ACTIVE = 'ACTIVE'; case FREEZE = 'FREEZE'; case PRE_FREEZE = 'PRE_FREEZE'; case PRE_UNFREEZE = 'PRE_UNFREEZE'; case RISK_FREEZE = 'RISK_FREEZE'; case ADMIN_FREEZE = 'ADMIN_FREEZE'; case PRE_CANCEL = 'PRE_CANCEL'; case CANCEL = 'CANCEL'; case RISK_CANCEL = 'RISK_CANCEL'; case ADMIN_CANCEL = 'ADMIN_CANCEL'; case EXPIRED = 'EXPIRED'; }
enum CardOrganization: string { case VISA = 'VISA'; case MASTER_CARD = 'MASTER_CARD'; case DINNERS = 'DINNERS'; case AMEX = 'AMEX'; case JCB = 'JCB'; case DISCOVER = 'DISCOVER'; }
enum SharedAccountStatus: string { case APPLYING = 'APPLYING'; case ACTIVE = 'ACTIVE'; case FREEZE = 'FREEZE'; case PRE_FREEZE = 'PRE_FREEZE'; case PRE_UNFREEZE = 'PRE_UNFREEZE'; case RISK_FREEZE = 'RISK_FREEZE'; case ADMIN_FREEZE = 'ADMIN_FREEZE'; case PRE_CANCEL = 'PRE_CANCEL'; case CANCEL = 'CANCEL'; case RISK_CANCEL = 'RISK_CANCEL'; case ADMIN_CANCEL = 'ADMIN_CANCEL'; }
enum AvailabilityFlag: int { case NO = 0; case YES = 1; }
enum SharedAccountTransactionType: int { case DEPOSIT = 101; case WITHDRAW = 102; case SHARED_ACCOUNT_ADJUST = 103; case CARD_TRANSACTION = 1; }
enum TradeStatus: string { case SUCCESS = 'SUCCESS'; case FAIL = 'FAIL'; case PROCESSING = 'PROCESSING'; case PENDING = 'PENDING'; case REFUND_PENDING = 'REFUND_PENDING'; case REFUND = 'REFUND'; }
enum MemberTradeType: string { case AUTH = 'AUTH'; case AUTH_VERIFY = 'AUTH_VERIFY'; case AUTH_REVOKE = 'AUTH_REVOKE'; case AUTH_REFUND = 'AUTH_REFUND'; case AUTH_CORRECTIVE = 'AUTH_CORRECTIVE'; case AUTH_REFUND_REVERSAL = 'AUTH_REFUND_REVERSAL'; case DISPUTED_REFUSAL = 'DISPUTED_REFUSAL'; }
enum TransactionDirection: int { case TRANSFER_IN = 1; case TRANSFER_OUT = 2; }
enum ProcessStatus: string { case PENDING = 'PENDING'; case PROCESSING = 'PROCESSING'; case SUCCESS = 'SUCCESS'; case FAIL = 'FAIL'; }
enum SharedAccountOpenStatus: string { case SUCCESS = 'SUCCESS'; case FAIL = 'FAIL'; case PROCESSING = 'PROCESSING'; }
enum WalletTransactionType: int { case DEPOSIT = 101; case WITHDRAW = 102; case WALLET_ADJUST = 103; case FREEZE = 201; case UNFREEZE = 202; case APPLY_CARD_FEE = 1; case CARD_RECHARGE = 2; case CARD_RECHARGE_FEE = 3; case CARD_REVOKE_FEE = 4; case CARD_MIN_AMOUNT_FEE = 5; case CARD_AUTH_FEE = 6; case CARD_CROSS_BORDER_FEE = 7; case CARD_OUT = 8; case REFUND = 9; case CARD_OUT_FEE = 10; case TRANSFER_IN = 11; case TRANSFER_OUT = 12; case APPLY_ACCOUNT_FEE = 13; case ACCOUNT_SERVICE_FEE = 14; case SHARED_ACCOUNT_RECHARGE = 15; case SHARED_ACCOUNT_REDUCE = 16; case CARD_REFUND_FEE = 17; }
enum WalletTransactionDirection: int { case TRANSFER_INTO = 1; case TRANSFER_OUT = 2; }
enum WalletTransactionStatus: int { case PROCESS = 0; case SUCCESS = 1; case FAIL = 2; }
