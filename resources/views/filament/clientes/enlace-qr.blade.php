<div style="text-align:center;padding:8px 4px 16px">
    @php($qrDataUri = \App\Support\QrCodeDataUri::fromText($record->urlPublica()))

    @if ($qrDataUri)
        <img
            src="{{ $qrDataUri }}"
            alt="Código QR para {{ $record->NOMBRE }}"
            style="display:block;width:min(100%,320px);margin:0 auto 20px;border:1px solid #e5e7eb;border-radius:18px;background:#fff;padding:10px"
        >
    @else
        <div style="width:min(100%,320px);margin:0 auto 20px;border:1px dashed #d6b247;border-radius:18px;background:#fffdf5;padding:22px 16px;color:#7a5b00;font-size:13px;font-weight:700">
            QR no disponible. Falta instalar dependencias en el servidor.
        </div>
    @endif

    <p style="margin:0 0 8px;color:#6b7280;font-size:13px">Comparte este QR o copia el enlace:</p>
    <div style="padding:12px 14px;border-radius:12px;background:#f5f3e9;word-break:break-all;font-size:13px;font-weight:600">
        {{ $record->urlPublica() }}
    </div>
    <button
        type="button"
        x-data="{ copied: false }"
        x-on:click="navigator.clipboard.writeText(@js($record->urlPublica())); copied = true; setTimeout(() => copied = false, 1500)"
        style="margin-top:14px;padding:11px 18px;border:0;border-radius:10px;background:#17221f;color:white;font-weight:700;cursor:pointer"
    >
        <span x-show="!copied">Copiar enlace</span>
        <span x-show="copied" x-cloak>Enlace copiado</span>
    </button>
</div>
