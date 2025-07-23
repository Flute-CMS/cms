@if (!empty(page()->getBlocks()))
    <section class="container mb-4">
        <div class="row">
            <div class="col-md-12">
                <div class="page-widgets" id="page-widgets">
                    {!! page()->renderAllWidgets() !!}
                </div>
            </div>
        </div>
    </section>
@endif
