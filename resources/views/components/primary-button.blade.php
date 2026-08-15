<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ui-button-primary']) }}>
    {{ $slot }}
</button>
