@php $moneyCols = ['revenue', 'discount']; @endphp

@if ($rows->isEmpty())
    <p class="rp-label">Bu aralıkta veri yok.</p>
@else
    <table class="rp-table">
        <thead>
            <tr>
                @foreach ($cols as $label => $key)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($cols as $label => $key)
                        <td>{{ in_array($key, $moneyCols, true) ? money((float) $row->{$key}) : $row->{$key} }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
