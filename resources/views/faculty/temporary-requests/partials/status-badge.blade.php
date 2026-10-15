<span @class([
    'status-badge',
    'status-occupied' => $status === 'pending',
    'status-available' => $status === 'approved',
    'status-maintenance' => in_array($status, ['rejected', 'cancelled']),
])>
    {{ ucfirst($status) }}
</span>
