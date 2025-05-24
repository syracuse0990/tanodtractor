<button {{ $attributes->merge(['type' => 'submit', 'class' => 'px-4 py-2 border text-white login-btn']) }}>
    {{ $slot }}
</button>
