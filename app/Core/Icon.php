<?php
declare(strict_types=1);
namespace App\Core;
final class Icon
{
    public static function svg(string $name,string $class=''): string
    {
        $paths=[
            'home'=>'<path d="M3 11.2 12 4l9 7.2V21h-6v-6H9v6H3z"/>',
            'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/>',
            'ball'=>'<circle cx="12" cy="12" r="9"/><path d="m9 9 3-2 3 2-1 4h-4zm1 4-3 2m7-2 3 2M12 7V3"/>',
            'users'=>'<circle cx="9" cy="8" r="3"/><path d="M3 19c.6-3.4 2.7-5 6-5s5.4 1.6 6 5M16 5.5a3 3 0 0 1 0 5.5m.5 3c2.7.3 4.1 1.9 4.5 5"/>',
            'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4.5 21c.8-4.3 3.4-6.5 7.5-6.5s6.7 2.2 7.5 6.5"/>',
            'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
            'pin'=>'<path d="M12 21s6-5.7 6-11a6 6 0 1 0-12 0c0 5.3 6 11 6 11Z"/><circle cx="12" cy="10" r="2"/>',
            'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18"/>',
            'message'=>'<path d="M4 5h16v12H9l-5 4z"/><path d="M8 9h8m-8 4h5"/>',
            'bell'=>'<path d="M18 9a6 6 0 1 0-12 0c0 6-3 6-3 8h18c0-2-3-2-3-8M9.5 21h5"/>',
            'shield'=>'<path d="M12 3 20 6v6c0 5-3.2 8-8 10-4.8-2-8-5-8-10V6z"/><path d="m8.5 12 2.2 2.2 4.8-5"/>',
            'check'=>'<path d="m5 12 4 4L19 6"/>',
            'bolt'=>'<path d="M13 2 5 14h6l-1 8 9-13h-6z"/>',
            'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
            'play'=>'<path d="M8 5v14l11-7z"/>',
            'settings'=>'<circle cx="12" cy="12" r="3"/><path d="M19 13.5v-3l-2-.6a7 7 0 0 0-.8-1.8l1-1.8-2.1-2.1-1.8 1a7 7 0 0 0-1.8-.8L11 2H8l-.6 2a7 7 0 0 0-1.8.8l-1.8-1-2.1 2.1 1 1.8a7 7 0 0 0-.8 1.8L0 10v3l2 .6a7 7 0 0 0 .8 1.8l-1 1.8 2.1 2.1 1.8-1a7 7 0 0 0 1.8.8L8 22h3l.6-2a7 7 0 0 0 1.8-.8l1.8 1 2.1-2.1-1-1.8a7 7 0 0 0 .8-1.8z" transform="translate(2 -0.5) scale(.83)"/>',
        ];
        if(!isset($paths[$name])) return '';
        $cls=$class!==''?' class="'.htmlspecialchars($class,ENT_QUOTES,'UTF-8').'"':'';
        return '<svg'.$cls.' width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$paths[$name].'</svg>';
    }
}
