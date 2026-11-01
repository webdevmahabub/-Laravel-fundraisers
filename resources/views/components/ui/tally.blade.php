@props([
    'raised' => 0,
    'goal' => 0,
    'currency' => 'USD',
    'decimals' => 0,
    'showGoal' => true,
    'delay' => 0,
    'class' => '',
])

@php
    $raisedAmount = (float) $raised;
    $goalAmount = (float) $goal;

    $ratio = $goalAmount > 0 ? $raisedAmount / $goalAmount : 0;
    $ratio = max(0, min(1, $ratio));
    $percent = (int) round($ratio * 100);
    $complete = $ratio >= 1;
@endphp

<div {{ $attributes->merge(['class' => 'tally ' . ($complete ? 'tally--complete ' : '') . $class]) }}>
    <div class="tally__track"
         role="progressbar"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-valuenow="{{ $percent }}"
         aria-label="Funding progress">
        <div class="tally__fill" style="--pct: {{ $percent }}%; --delay: {{ $delay }}ms"></div>
        @if ($showGoal)
            <span class="tally__mark" aria-hidden="true"></span>
        @endif
    </div>

    <div class="tally__figures">
        <span class="tally__raised"><x-ui.money :value="$raisedAmount" :currency="$currency" :decimals="$decimals" /></span>

        @if ($showGoal)
            <span class="tally__goal">
                of <x-ui.money :value="$goalAmount" :currency="$currency" :decimals="$decimals" /> goal
            </span>
        @endif

        <span class="tally__pct">{{ $percent }}%</span>
    </div>
</div>
