<?php

namespace Xplodman\FilamentApproval\Enums;

enum RelationTypeEnum: string
{
    case BELONGS_TO = 'belongsTo';
    case BELONGS_TO_MANY = 'belongsToMany';
    case MORPH_TO_MANY = 'morphToMany';
    case HAS_MANY = 'hasMany';
    case MORPH_MANY = 'morphMany';
}
