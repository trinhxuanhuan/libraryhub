@props(['headers', 'data'])

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th scope="col">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($data as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}"
                        class="text-center text-muted">
                        Không có dữ liệu.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
