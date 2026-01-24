<div class="variables-list">
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th style="width: 200px;">{{ __('admin-notifications.fields.variable') ?? 'Переменная' }}</th>
                    <th>{{ __('admin-notifications.fields.description') ?? 'Описание' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($variables as $name => $description)
                    <tr>
                        <td>
                            <code class="bg-primary-lt px-2 py-1 rounded">{!! '{' . e($name) . '}' !!}</code>
                        </td>
                        <td>{{ $description }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="alert alert-info mt-3">
        <div class="d-flex align-items-start gap-2">
            <i class="ph-lightbulb ph-bold mt-1"></i>
            <div>
                <strong>{{ __('admin-notifications.hints.usage') ?? 'Использование' }}:</strong><br>
                {{ __('admin-notifications.hints.variables_usage') ?? 'Вставьте переменную в текст заголовка или содержимого, например: "Привет, {user_name}!"' }}
            </div>
        </div>
    </div>
</div>
