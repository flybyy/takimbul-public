<?php

declare(strict_types=1);

namespace App\Support;

final class VerificationBadge
{
    public static function render(array|bool $subject): string
    {
        $verified=is_bool($subject)?$subject:!empty($subject['profile_verified_at']);
        if(!$verified)return '';
        $label='TakımBul tarafından doğrulanmış profil';
        return '<span class="tb-verified-badge" role="img" aria-label="'.$label.'" title="'.$label.'"><svg width="17" height="17" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"></circle><path d="M7.8 12.2 10.5 15l5.8-6.2"></path></svg></span>';
    }

    public static function officialStaff(): string
    {
        $label='Resmi TakımBul ekip hesabı';
        return '<span class="tb-staff-badge" role="img" aria-label="'.$label.'" title="'.$label.'"><svg width="17" height="17" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"></circle><path d="M7.8 12.2 10.5 15l5.8-6.2"></path></svg></span>';
    }
}
