@php($iconName = in_array($name ?? '', array_keys(\Pcteckserv\CmsCore\Support\IconCatalog::options()), true) ? $name : '')
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($iconName)
        @case('check')<polyline points="20 6 9 17 4 12"/>@break
        @case('shield-check')<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><polyline points="9 12 11 14 15 10"/>@break
        @case('clock')<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>@break
        @case('award')<circle cx="12" cy="8" r="6"/><path d="m8.2 13.5-1.1 8.1 4.9-2.8 4.9 2.8-1.1-8.1"/>@break
        @case('hammer')<path d="m15 12-8.5 8.5a2.1 2.1 0 0 1-3-3L12 9"/><path d="m17.5 15 3-3L11 2.5l-3 3z"/><path d="m2 22 2-2"/>@break
        @case('house')<path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9M9 21v-7h6v7"/>@break
        @case('ruler')<path d="m21.3 8.7-6-6a2.4 2.4 0 0 0-3.4 0l-9.2 9.2a2.4 2.4 0 0 0 0 3.4l6 6a2.4 2.4 0 0 0 3.4 0l9.2-9.2a2.4 2.4 0 0 0 0-3.4Z"/><path d="m7.5 10.5 2 2m1-5 2 2m1-5 2 2m1 9 2 2"/>@break
        @case('wrench')<path d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3 18a2.1 2.1 0 0 0 3 3l5.3-5.3a5 5 0 0 0 6.4-6.4L14 13l-3-3z"/>@break
        @case('leaf')<path d="M20 4c-7 0-13 2-13 9a7 7 0 0 0 7 7c7 0 9-6 9-13V4z"/><path d="M3 21c3-6 7-9 13-12"/>@break
        @case('star')<polygon points="12 2 15.1 8.3 22 9.3 17 14.2 18.2 21 12 17.8 5.8 21 7 14.2 2 9.3 8.9 8.3 12 2"/>@break
        @case('map-pin')<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>@break
        @case('phone')<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 2.8a2 2 0 0 1-.6 1.7L7.7 9.5a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 1.7-.6l2.8.5a2 2 0 0 1 1.7 1.8Z"/>@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>@break
        @case('heart')<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/>@break
        @case('sparkles')<path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2L12 3Z"/><path d="m19 14 1.1 2.9L23 18l-2.9 1.1L19 22l-1.1-2.9L15 18l2.9-1.1L19 14Z"/>@break
    @endswitch
</svg>
