<?php

namespace App\Enums;

enum DealStage: string
{
    case DOC_SENT = 'doc sent';
    case DOC_SIGNED = 'doc signed';
    case COMPLIANT = 'compliant';
    case READY_FOR_PAYMENT = 'ready for payment';
    case PAID = 'paid';
    case LOST = 'lost';
}
