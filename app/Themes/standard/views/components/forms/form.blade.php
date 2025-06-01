<form action="{{ $form->getAction() }}" method="{{ strtolower($form->getMethod()) }}" {!! html_attributes($form->getAttributes()) !!}>
    @csrf
    @if (!in_array($form->getMethod(), ['GET', 'POST']))
        @method($form->getMethod())
    @endif

    @foreach ($form->fields() as $field)
        {!! $field->render() !!}
    @endforeach
</form>
