@php
  $rawMsg = isset($exception) ? trim((string) $exception->getMessage()) : '';
  $handoff = request()->attributes->get('handoff')
    ?? \App\Modules\Identity\AccessHandoff::resolve(request(), auth()->user(), $rawMsg);
@endphp
@include('errors.layout', [
  'heading' => $handoff['heading'],
  'attempted' => $handoff['attempted'] ?? null,
  'text' => $handoff['text'],
  'handledBy' => $handoff['handled_by'] ?? null,
  'actionUrl' => $handoff['action_url'] ?? null,
  'actionLabel' => $handoff['action_label'] ?? null,
])
