@props(['status', 'type' => 'found'])

@php
  /**
   * Status badge component.
   * $type: 'found' for FoundItem statuses, 'lost' for LostReport statuses.
   */
  $foundMap = [
    '保管中'       => 'badge-green',
    '返還済'       => 'badge-blue',
    '警察提出済'   => 'badge-gray',
    '期間満了処分' => 'badge-red',
  ];

  $lostMap = [
    '探索中'   => 'badge-orange',
    '解決済'   => 'badge-blue',
    'キャンセル' => 'badge-gray',
  ];

  $foundIcons = [
    '保管中'       => 'package',
    '返還済'       => 'handshake',
    '警察提出済'   => 'shield',
    '期間満了処分' => 'trash-2',
  ];

  $lostIcons = [
    '探索中'   => 'search',
    '解決済'   => 'check-circle',
    'キャンセル' => 'x-circle',
  ];

  if ($type === 'lost') {
    $cls  = $lostMap[$status]  ?? 'badge-gray';
    $icon = $lostIcons[$status] ?? 'circle';
  } else {
    $cls  = $foundMap[$status]  ?? 'badge-gray';
    $icon = $foundIcons[$status] ?? 'circle';
  }
@endphp

<span class="badge {{ $cls }}" role="status">
  <i data-lucide="{{ $icon }}" style="width:11px;height:11px"></i>
  {{ $status }}
</span>
