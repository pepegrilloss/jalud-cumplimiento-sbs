@php
    $offColor = $getOffColor() ?? 'gray';
    $onColor = $getOnColor() ?? 'primary';
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :inline-label-vertical-alignment="\Filament\Support\Enums\VerticalAlignment::Center"
>
    @capture($content)
        <button
            x-data="{
                state: false,
                checkboxes: function () {
                    return Array.from(document.querySelectorAll('.fi-fo-checkbox-list-option-label input[type=checkbox]'))
                        .filter((checkbox) => ! checkbox.disabled)
                },
                syncState: function () {
                    const checkboxes = this.checkboxes()
                    this.state = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked)
                },
                toggleAll: function () {
                    const targetState = ! this.state

                    this.checkboxes().forEach((checkbox) => {
                        if (checkbox.checked !== targetState) {
                            checkbox.click()
                        }
                    })

                    this.state = targetState
                    this.$nextTick(() => this.syncState())
                },
                init: function () {
                    this.$nextTick(() => this.syncState())

                    Livewire.hook('commit', ({ component, succeed }) => {
                        succeed(() => {
                            if (component.id === @js($this->getId())) {
                                this.$nextTick(() => this.syncState())
                            }
                        })
                    })
                },
            }"
            x-init="init()"
            x-on:click="toggleAll()"
            x-on:change.window="
                if ($event.target.matches('.fi-fo-checkbox-list-option-label input[type=checkbox]')) {
                    syncState()
                }
            "
            x-bind:aria-checked="state"
            x-bind:class="state
                ? '{{ match ($onColor) { 'gray' => 'fi-color-gray bg-gray-200 dark:bg-gray-700', default => 'fi-color-custom bg-custom-600' } }}'
                : '{{ match ($offColor) { 'gray' => 'fi-color-gray bg-gray-200 dark:bg-gray-700', default => 'fi-color-custom bg-custom-600' } }}'"
            x-bind:style="state
                ? '{{ \Filament\Support\get_color_css_variables($onColor, shades: [600], alias: 'forms::components.toggle.on') }}'
                : '{{ \Filament\Support\get_color_css_variables($offColor, shades: [600], alias: 'forms::components.toggle.off') }}'"
            {{
                $attributes
                    ->merge([
                        'autofocus' => $isAutofocused(),
                        'disabled' => $isDisabled(),
                        'id' => $getId(),
                        'role' => 'switch',
                        'type' => 'button',
                    ], escape: false)
                    ->merge($getExtraAttributes(), escape: false)
                    ->merge($getExtraAlpineAttributes(), escape: false)
                    ->class(['fi-fo-toggle relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent outline-none transition-colors duration-200 ease-in-out disabled:pointer-events-none disabled:opacity-70'])
            }}
        >
            <span
                class="relative inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                x-bind:class="{
                    'translate-x-5 rtl:-translate-x-5': state,
                    'translate-x-0': ! state,
                }"
            >
                <span class="absolute inset-0 flex h-full w-full items-center justify-center" x-bind:class="state ? 'opacity-0' : 'opacity-100'" aria-hidden="true">
                    @if ($hasOffIcon())
                        <x-filament::icon :icon="$getOffIcon()" class="fi-fo-toggle-off-icon h-3 w-3 text-gray-400" />
                    @endif
                </span>

                <span class="absolute inset-0 flex h-full w-full items-center justify-center" x-bind:class="state ? 'opacity-100' : 'opacity-0'" aria-hidden="true">
                    @if ($hasOnIcon())
                        <x-filament::icon :icon="$getOnIcon()" class="fi-fo-toggle-on-icon h-3 w-3 text-primary-600" />
                    @endif
                </span>
            </span>
        </button>
    @endcapture

    @if ($isInline())
        <x-slot name="labelPrefix">
            {{ $content() }}
        </x-slot>
    @else
        {{ $content() }}
    @endif
</x-dynamic-component>
