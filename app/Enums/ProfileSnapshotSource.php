<?php

namespace App\Enums;

enum ProfileSnapshotSource: string
{
    case AGENT_UPDATE = 'agent_update';
    case PROJECT_REGISTRATION = 'project_registration';
    case PROJECT_UPDATE = 'project_update';
}
