<x-mail::message>

    <p>Код подтверждения: {{ $code }}</p>

    <x-slot:subcopy>
        <sub>ℹ️ Это письмо отправлено автоматически и не требует ответа</sub><br><br>
    </x-slot:subcopy>
</x-mail::message>
