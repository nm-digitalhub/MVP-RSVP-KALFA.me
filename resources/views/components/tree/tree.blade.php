<?php

use Livewire\Component;

new class extends Component
{
    public bool $initExpanded = true;

    public function mount(bool $initExpanded = true)
    {
        $this->initExpanded = $initExpanded;
    }
};
?>

<div
    x-data
    x-init="
        if (!Alpine.store('productTree')) {
            Alpine.store('productTree', {
                // הגדרות תצוגה
                showIcons: true,
                showStatuses: true,
                showIdentifiers: true,
                showComments: true,
                
                // סוג ההערות שיוצגו (all / unread)
                commentType: 'all', 

                // ניהול המצב ההתחלתי של הענפים
                initExpanded: @js($initExpanded),

                // ניהול 'מצב בחור' (Active State)
                selectedNode: null,

                selectNode(nodeId) {
                    this.selectedNode = nodeId;
                }
            });
        }
    "
    {{ $attributes->merge(['class' => 'space-y-1 relative']) }}
>
    {{-- כל הקומפוננטות הפנימיות (Toolbar, Branches, Nodes) ירונדרו כאן --}}
    {{ $slot }}
</div>