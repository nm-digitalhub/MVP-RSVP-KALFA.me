<?php

new class extends \Livewire\Component
{
    //
};
?>

<div>
    <button wire:click="$wire.$js.test" dusk="test">Test</button>
</div>

<script>
    $wire.$js.test = () => {
        window.test = 'through dollar js'
    }
</script>
