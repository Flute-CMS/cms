{{-- Renders a list of skeleton descriptors. Receives $skeletons (array of descriptors). --}}
@foreach ($skeletons as $skel)
    @include('admin::partials.layouts.skeleton-item', ['skel' => $skel])
@endforeach
