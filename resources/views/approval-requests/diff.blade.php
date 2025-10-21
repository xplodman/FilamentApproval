@php
    $original = $original ?? [];
    $proposed = $proposed ?? [];
    $debugMode = config('filamentapproval.debug', false);

    // Get model casts from the approvable model, not the approval request
    $modelCasts = [];
    if (isset($record) && $record->approvable_type && $record->approvable_id) {
        try {
            $approvableModel = new $record->approvable_type();
            if (method_exists($approvableModel, 'getCasts')) {
                $modelCasts = $approvableModel->getCasts();
            }
        } catch (\Exception $e) {
            // Fallback: try to get casts from the approval request if approvable model fails
            if (method_exists($record, 'getCasts')) {
                $modelCasts = $record->getCasts();
            }
        }
    }

    $keys = collect(array_unique(array_merge(array_keys($original), array_keys($proposed))))
        ->reject(function ($k) { // hide mostly-noise fields from the diff
            return in_array($k, ['id', 'created_at', 'updated_at'], true);
        })
        ->values()
        ->all();

    $isAssoc = function (array $arr) {
        if ($arr === []) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    };

    $booleanish = function ($v) {
        if (is_bool($v)) return $v;
        if (is_int($v)) {
            if ($v === 1) return true;
            if ($v === 0) return false;
        }
        if (is_string($v)) {
            $lv = strtolower(trim($v));
            if (in_array($lv, ['1','true','yes','on'], true)) return true;
            if (in_array($lv, ['0','false','no','off',''], true)) return false;
        }
        return null; // not booleanish
    };

    $normalizeDateTime = function ($value, $castType = null) {
        if (is_null($value)) return null;

        // Handle datetime casts or datetime-like fields
        $isDateTime = $castType === 'datetime' || $castType === 'date' ||
                     (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value));

        if ($isDateTime) {
            try {
                // Try to parse as Carbon/DateTime and convert to UTC
                if (is_string($value)) {
                    $carbon = \Carbon\Carbon::parse($value);
                    return $carbon->utc()->format('Y-m-d H:i:s');
                }
                if ($value instanceof \DateTimeInterface) {
                    return \Carbon\Carbon::instance($value)->utc()->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // If parsing fails, return as string for comparison
                return (string) $value;
            }
        }

        return $value;
    };

    $normalizeScalar = function ($v, $castType = null) use ($booleanish, $normalizeDateTime) {
        if (is_null($v)) return null;

        // Handle datetime normalization first
        $normalized = $normalizeDateTime($v, $castType);
        if ($normalized !== $v) return $normalized;

        $b = $booleanish($v);
        if ($b !== null) return $b;
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return (string) (0 + $v);
        if (is_string($v)) {
            $lv = strtolower($v);
            if (is_numeric($v)) return (string) (0 + $v);
            return $v;
        }
        return $v;
    };

    $asListOfScalars = function ($value) use ($isAssoc) {
        if (! is_array($value)) return null;

        // Handle empty arrays - they should be considered equal
        if (empty($value)) return [];

        $list = $isAssoc($value) ? array_values($value) : $value;
        // flatten one level for maps like [{id:..}, ...] is out of scope; keep scalars only
        $scalars = array_map(function ($v) {
            if (is_scalar($v) || is_null($v)) return $v;
            return json_encode($v);
        }, $list);
        // string-cast for set operations
        $scalars = array_map(fn($v) => (string) (is_bool($v) ? ($v ? 'true' : 'false') : ($v ?? '')), $scalars);
        sort($scalars);
        $scalars = array_values(array_unique($scalars));
        return $scalars;
    };

    $looselyEqual = function ($a, $b, $key = null) use ($normalizeScalar, $asListOfScalars, $modelCasts) {
        // Get cast type for this field
        $castType = $modelCasts[$key] ?? null;

        // Handle empty arrays - they should be considered equal
        if (is_array($a) && is_array($b)) {
            if (empty($a) && empty($b)) return true;

            $la = $asListOfScalars($a);
            $lb = $asListOfScalars($b);
            if (is_array($la) && is_array($lb)) {
                return implode('\n', $la) === implode('\n', $lb);
            }
            return json_encode($a) === json_encode($b);
        }

        // Handle datetime comparison - also check for common datetime field patterns
        $isDateTimeField = $castType === 'datetime' || $castType === 'date' ||
                          (is_string($key) && preg_match('/_at$|_date$|_time$/', $key));

        if ($isDateTimeField) {
            $normalizedA = $normalizeScalar($a, $castType);
            $normalizedB = $normalizeScalar($b, $castType);
            return $normalizedA === $normalizedB;
        }

        return $normalizeScalar($a, $castType) === $normalizeScalar($b, $castType);
    };

    $formatScalar = function ($value) {
        if ($value === null) return '—';
        if (is_bool($value)) return $value ? 'true' : 'false';
        return (string) $value;
    };

    $formatValue = function ($value, $castType = null) use ($isAssoc, $formatScalar, $normalizeDateTime) {
        if (is_array($value)) {
            if (empty($value)) {
                return '—'; // Show empty arrays as dash
            }
            if ($isAssoc($value)) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            return json_encode(array_values($value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        // Format datetime values nicely in UTC
        if ($castType === 'datetime' || $castType === 'date') {
            $normalized = $normalizeDateTime($value, $castType);
            if ($normalized !== $value) {
                try {
                    $carbon = \Carbon\Carbon::parse($value);
                    return $carbon->utc()->format('Y-m-d H:i:s') . ' UTC';
                } catch (\Exception $e) {
                    return $normalized . ' UTC';
                }
            }
        }

        return $formatScalar($value);
    };

    $changedOnly = true; // show only changed fields for clarity

    // Fallback array-like keys for when model casts aren't available
    $arrayLikeKeys = ['attachments','attachment','files','images','photos','documents'];

    $coerceArray = function ($v) {
        if (is_string($v)) {
            $decoded = json_decode($v, true);
            if (is_array($decoded)) return $decoded;
            return [$v];
        }
        if (is_array($v)) return $v;
        if (is_null($v)) return [];
        return [$v];
    };

    // Smart array field detection using model casts and content analysis
    $isArrayField = function ($key, $value, $castType = null) use ($arrayLikeKeys) {
        // 1. Check model casts first (most reliable)
        if ($castType === 'array') return true;

        // 2. Check if it's already an array
        if (is_array($value)) return true;

        // 3. Check if it's a JSON string that decodes to an array
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) return true;
        }

        // 4. Check against known array-like field names (fallback)
        if (in_array($key, $arrayLikeKeys, true)) return true;

        // 5. Check for common array field patterns
        $arrayPatterns = [
            '/_ids$/',           // field_ids, user_ids, etc.
            '/_list$/',          // field_list, etc.
            '/_array$/',         // field_array, etc.
            '/^tags/',           // tags, tags_*, etc.
            '/^categories/',     // categories, categories_*, etc.
            '/^options/',        // options, options_*, etc.
        ];

        foreach ($arrayPatterns as $pattern) {
            if (preg_match($pattern, $key)) return true;
        }

        return false;
    };
@endphp

<div>
    <p><small>Comparing proposed changes to original values.</small></p>
    @if ($debugMode)
        <div style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; padding: 8px; margin-bottom: 16px;">
            <p style="margin: 0; font-weight: 600; color: #92400e;"><strong>🐛 Debug Mode Enabled</strong></p>
            <p style="margin: 4px 0 0 0; font-size: 0.875em; color: #92400e;">
                Showing detailed debug information including raw values, cast types, normalization details, and comparison logic.
            </p>
        </div>
    @endif

    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align:left; border-bottom: 1px solid #e5e7eb; padding: 6px;">Field</th>
                <th style="text-align:left; border-bottom: 1px solid #e5e7eb; padding: 6px;">Original</th>
                <th style="text-align:left; border-bottom: 1px solid #e5e7eb; padding: 6px;">Proposed</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($keys as $key)
                @php
                    $o = $original[$key] ?? null;
                    $p = $proposed[$key] ?? null;
                    $castType = $modelCasts[$key] ?? null;
                    $isArrayish = $isArrayField($key, $o, $castType) || $isArrayField($key, $p, $castType);
                    $oArr = $isArrayish ? $coerceArray($o) : (is_array($o) ? $o : null);
                    $pArr = $isArrayish ? $coerceArray($p) : (is_array($p) ? $p : null);
                    $compareLeft = $oArr !== null ? $oArr : $o;
                    $compareRight = $pArr !== null ? $pArr : $p;
                    $isChanged = ! $looselyEqual($compareLeft, $compareRight, $key);

                    // Debug datetime fields
                    $isDateTimeField = $castType === 'datetime' || $castType === 'date' ||
                                      (is_string($key) && preg_match('/_at$|_date$|_time$/', $key));
                @endphp

                @if (! $changedOnly || $isChanged)
                    <tr>
                        <td style="vertical-align: top; padding: 6px; width: 25%; font-weight: 600;">
                            {{ $key }}
                            @if ($debugMode)
                                <div style="font-size: 0.75em; color: #6b7280; margin-top: 2px;">
                                    <div><strong>Cast:</strong> {{ $castType ?? 'none' }}</div>
                                    <div><strong>Type:</strong> {{ gettype($o) }} → {{ gettype($p) }}</div>
                                    <div><strong>Changed:</strong> {{ $isChanged ? 'Yes' : 'No' }}</div>
                                    @if ($isDateTimeField)
                                        <div><strong>DateTime Field:</strong> Yes</div>
                                    @endif
                                    @if ($isArrayish)
                                        <div><strong>Array Field:</strong> Yes</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td style="vertical-align: top; padding: 6px; width: 37.5%;">
                            @if ($debugMode)
                                <div style="font-size: 0.75em; color: #6b7280; margin-bottom: 4px;">
                                    <div><strong>Raw:</strong> {{ json_encode($o) }}</div>
                                    <div><strong>Normalized:</strong> {{ json_encode($normalizeScalar($o, $castType)) }}</div>
                                </div>
                            @endif
                            @if (is_array($oArr) && is_array($pArr))
                                @php
                                    // Check if both are associative arrays
                                    $oIsAssoc = $isAssoc($oArr);
                                    $pIsAssoc = $isAssoc($pArr);
                                    
                                    if ($oIsAssoc || $pIsAssoc) {
                                        // Handle associative arrays - show key-value pairs
                                        $oKeys = $oIsAssoc ? array_keys($oArr) : [];
                                        $pKeys = $pIsAssoc ? array_keys($pArr) : [];
                                        $allKeys = array_unique(array_merge($oKeys, $pKeys));
                                        
                                        $removedKeys = array_diff($oKeys, $pKeys);
                                        $addedKeys = array_diff($pKeys, $oKeys);
                                        $commonKeys = array_intersect($oKeys, $pKeys);
                                    } else {
                                        // Handle indexed arrays - show values only
                                        $oSet = collect($asListOfScalars($o) ?? []);
                                        $pSet = collect($asListOfScalars($p) ?? []);
                                        $removed = $oSet->diff($pSet)->values()->all();
                                        $common = $oSet->intersect($pSet)->values()->all();
                                    }
                                @endphp
                                
                                @if ($oIsAssoc || $pIsAssoc)
                                    {{-- Show associative array changes --}}
                                    @foreach ($commonKeys as $key)
                                        @php
                                            $oVal = $oArr[$key] ?? null;
                                            $pVal = $pArr[$key] ?? null;
                                            $valChanged = !$looselyEqual($oVal, $pVal, $key);
                                        @endphp
                                        @if ($valChanged)
                                            <div style="text-decoration: line-through; color: #b91c1c;"><strong>{{ $key }}:</strong> {{ $formatScalar($oVal) }}</div>
                                        @else
                                            <div><strong>{{ $key }}:</strong> {{ $formatScalar($oVal) }}</div>
                                        @endif
                                    @endforeach
                                    @foreach ($removedKeys as $key)
                                        <div style="text-decoration: line-through; color: #b91c1c;"><strong>{{ $key }}:</strong> {{ $formatScalar($oArr[$key]) }}</div>
                                    @endforeach
                                @else
                                    {{-- Show indexed array changes --}}
                                    @foreach ($common as $val)
                                        <div>{{ $val }}</div>
                                    @endforeach
                                    @foreach ($removed as $val)
                                        <div style="text-decoration: line-through; color: #b91c1c;">{{ $val }}</div>
                                    @endforeach
                                @endif
                            @elseif (is_array($o) && $isAssoc($o))
                                @foreach ($o as $k => $v)
                                    <div><strong>{{ $k }}:</strong> {{ $formatScalar($v) }}</div>
                                @endforeach
                            @else
                                <pre style="white-space: pre-wrap; margin: 0;">{{ $formatValue($o, $castType) }}</pre>
                            @endif
                        </td>
                        <td style="vertical-align: top; padding: 6px; width: 37.5%;">
                            @if ($debugMode)
                                <div style="font-size: 0.75em; color: #6b7280; margin-bottom: 4px;">
                                    <div><strong>Raw:</strong> {{ json_encode($p) }}</div>
                                    <div><strong>Normalized:</strong> {{ json_encode($normalizeScalar($p, $castType)) }}</div>
                                </div>
                            @endif
                            @if (is_array($oArr) && is_array($pArr))
                                @php
                                    // Check if both are associative arrays
                                    $oIsAssoc = $isAssoc($oArr);
                                    $pIsAssoc = $isAssoc($pArr);
                                    
                                    if ($oIsAssoc || $pIsAssoc) {
                                        // Handle associative arrays - show key-value pairs
                                        $oKeys = $oIsAssoc ? array_keys($oArr) : [];
                                        $pKeys = $pIsAssoc ? array_keys($pArr) : [];
                                        $allKeys = array_unique(array_merge($oKeys, $pKeys));
                                        
                                        $removedKeys = array_diff($oKeys, $pKeys);
                                        $addedKeys = array_diff($pKeys, $oKeys);
                                        $commonKeys = array_intersect($oKeys, $pKeys);
                                    } else {
                                        // Handle indexed arrays - show values only
                                        $oSet = collect($asListOfScalars($o) ?? []);
                                        $pSet = collect($asListOfScalars($p) ?? []);
                                        $added = $pSet->diff($oSet)->values()->all();
                                        $common = $oSet->intersect($pSet)->values()->all();
                                    }
                                @endphp
                                
                                @if ($oIsAssoc || $pIsAssoc)
                                    {{-- Show associative array changes --}}
                                    @foreach ($commonKeys as $key)
                                        @php
                                            $oVal = $oArr[$key] ?? null;
                                            $pVal = $pArr[$key] ?? null;
                                            $valChanged = !$looselyEqual($oVal, $pVal, $key);
                                        @endphp
                                        @if ($valChanged)
                                            <div style="color: #166534;"><strong>{{ $key }}:</strong> {{ $formatScalar($pVal) }}</div>
                                        @else
                                            <div><strong>{{ $key }}:</strong> {{ $formatScalar($pVal) }}</div>
                                        @endif
                                    @endforeach
                                    @foreach ($addedKeys as $key)
                                        <div style="color: #166534;">+ <strong>{{ $key }}:</strong> {{ $formatScalar($pArr[$key]) }}</div>
                                    @endforeach
                                @else
                                    {{-- Show indexed array changes --}}
                                    @foreach ($common as $val)
                                        <div>{{ $val }}</div>
                                    @endforeach
                                    @foreach ($added as $val)
                                        <div style="color: #166534;">+ {{ $val }}</div>
                                    @endforeach
                                @endif
                            @elseif (is_array($p) && $isAssoc($p))
                                @foreach ($p as $k => $v)
                                    <div><strong>{{ $k }}:</strong> {{ $formatScalar($v) }}</div>
                                @endforeach
                            @else
                                <pre style="white-space: pre-wrap; margin: 0;">{{ $formatValue($p, $castType) }}</pre>
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <p><small>Only changed fields are shown. Hidden: id/created_at/updated_at.</small></p>
</div>


