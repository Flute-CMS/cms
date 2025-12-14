<li class="table__columns-item">
    <x-forms.field>
        <x-fields.checkbox name="columns[]" value="{{ $slug }}" id="{{ $slug }}" :checked="$defaultHidden == 'false'"
            label="{{ $title }}" />
    </x-forms.field>
</li>
