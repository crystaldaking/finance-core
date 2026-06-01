<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Audit;

enum ActorType: string
{
    case System = 'system';
    case Admin = 'admin';
    case User = 'user';
    case Organization = 'organization';
    case Service = 'service';
    case Scheduler = 'scheduler';
    case Cli = 'cli';
    case Agent = 'agent';
}
