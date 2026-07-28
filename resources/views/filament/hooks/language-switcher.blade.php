<div style="display: flex; align-items: center; padding: 0 0.5rem;">
    @if(app()->getLocale() === 'ar')
        <a href="{{ route('lang.switch', 'en') }}" 
           title="Switch to English (LTR)"
           style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; font-weight: 600; padding: 0.375rem 0.75rem; border-radius: 0.5rem; background-color: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE; text-decoration: none; transition: background-color 0.2s;"
           onmouseover="this.style.backgroundColor='#E0E7FF'"
           onmouseout="this.style.backgroundColor='#EEF2FF'">
            <x-heroicon-o-language style="width: 1.125rem; height: 1.125rem; flex-shrink: 0;" />
            <span>English (LTR)</span>
        </a>
    @else
        <a href="{{ route('lang.switch', 'ar') }}" 
           title="التغيير إلى العربية (RTL)"
           style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; font-weight: 600; padding: 0.375rem 0.75rem; border-radius: 0.5rem; background-color: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; text-decoration: none; transition: background-color 0.2s;"
           onmouseover="this.style.backgroundColor='#D1FAE5'"
           onmouseout="this.style.backgroundColor='#ECFDF5'">
            <x-heroicon-o-language style="width: 1.125rem; height: 1.125rem; flex-shrink: 0;" />
            <span>العربية (RTL)</span>
        </a>
    @endif
</div>
