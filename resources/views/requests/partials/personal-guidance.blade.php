@php($personalStage = $request->requestorStatus())
<section id="employee-next-step" class="personal-guidance is-{{ $personalStage['tone'] }}" aria-labelledby="employeeNextStepHeading">
    <span class="personal-eyebrow">Request guidance</span>
    <span class="personal-status is-{{ $personalStage['tone'] }}"><i data-lucide="{{ $personalStage['icon'] }}" aria-hidden="true"></i>{{ $personalStage['label'] }}</span>
    <h2 id="employeeNextStepHeading">{{ $request->status === 'for_clarification' ? 'Clarification remarks and next step' : 'Your next step' }}</h2>
    <p>{{ $personalStage['next_step'] }}</p>
    @if($request->status === 'for_clarification')
        <blockquote>{{ $request->clarificationRemarks() ?? 'No clarification remarks were recorded. Review the activity history below for context.' }}</blockquote>
    @endif
    @if($request->status === 'pending')<p><a href="{{ route('requisitions.edit', $request) }}">Edit this pending request</a></p>@endif
    @if($request->isSlaActive())
        @if($request->isDeadlineOverdue())<p class="personal-deadline overdue"><i data-lucide="alarm-clock" aria-hidden="true"></i>At or past SLA deadline.</p>@elseif($request->isDeadlineNearing())<p class="personal-deadline near"><i data-lucide="clock-alert" aria-hidden="true"></i>Nearing SLA deadline.</p>@endif
        <p>Three-working-day processing target from submission, excluding weekends and institutional holidays. This is not a guaranteed pickup date.</p>
        <p>Request deadline: {{ $request->due_at?->format('M d, Y · g:i A') ?? 'Not recorded' }} ({{ config('app.timezone') }}).</p>
    @endif
    <p><a href="{{ route($request->isSlaActive() ? 'requests.index' : 'requests.archive') }}">{{ $request->isSlaActive() ? 'View my active requests' : 'View my archive' }}</a></p>
</section>
@include('requests.partials.personal-styles')
