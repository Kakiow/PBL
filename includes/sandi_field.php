<?php

// Input password + tombol lihat/sembunyikan. Opsi: id, name, required,
// minlength, autocomplete, aria_required, aria_invalid, aria_describedby,
// autofocus, label_tampil, label_sembunyi.
function sandi_field(array $opsi)
{
    $id = (string) ($opsi['id'] ?? '');
    $name = (string) ($opsi['name'] ?? $id);
    $label_tampil = (string) ($opsi['label_tampil'] ?? 'Tampilkan password');
    $label_sembunyi = (string) ($opsi['label_sembunyi'] ?? 'Sembunyikan password');

    $atribut = [
        'type="password"',
        'id="' . e($id) . '"',
        'name="' . e($name) . '"',
    ];

    if (!empty($opsi['autocomplete'])) {
        $atribut[] = 'autocomplete="' . e($opsi['autocomplete']) . '"';
    }
    if (!empty($opsi['minlength'])) {
        $atribut[] = 'minlength="' . (int) $opsi['minlength'] . '"';
    }
    if (!empty($opsi['required'])) {
        $atribut[] = 'required';
    }
    if (!empty($opsi['aria_required'])) {
        $atribut[] = 'aria-required="true"';
    }
    if (!empty($opsi['aria_invalid'])) {
        $atribut[] = 'aria-invalid="true"';
    }
    if (!empty($opsi['aria_describedby'])) {
        $atribut[] = 'aria-describedby="' . e($opsi['aria_describedby']) . '"';
    }
    if (!empty($opsi['autofocus'])) {
        $atribut[] = 'autofocus';
    }

    $ikon_lihat = '<svg class="ikon-lihat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />'
        . '<circle cx="12" cy="12" r="3" />'
        . '</svg>';

    $ikon_tutup = '<svg class="ikon-tutup" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="m3 3 18 18" />'
        . '<path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />'
        . '<path d="M9.9 5.7A10.5 10.5 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17.6 17.6 0 0 1-3.3 4.2" />'
        . '<path d="M6.2 6.2A17.4 17.4 0 0 0 2.5 12S6 18.5 12 18.5a10.2 10.2 0 0 0 4.3-.9" />'
        . '</svg>';

    echo '<span class="sandi-field">';
    echo '<input ' . implode(' ', $atribut) . '>';
    echo '<button type="button" class="sandi-toggle" data-sandi-toggle aria-controls="' . e($id)
        . '" aria-pressed="false" aria-label="' . e($label_tampil)
        . '" data-label-sembunyi="' . e($label_sembunyi) . '">'
        . $ikon_lihat . $ikon_tutup
        . '</button>';
    echo '</span>';
}
