<table>
    <thead>
        <tr>
            <th>الجهة</th>
            <th>عدد الزيارات</th>
            <th>عدد الأطباء</th>
        </tr>
    </thead>
    <tbody>
        @foreach($visits as $acc)
            <tr>
                <td>{{ $acc['account_name'] }}</td>
                <td>{{ $acc['total_visits'] }}</td>
                <td>{{ count($acc['doctors']) }}</td>
            </tr>
            @foreach($acc['doctors'] as $doc)
                <tr>
                    <td>-- {{ $doc['doctor_name'] }}</td>
                    <td>{{ $doc['visits_count'] }}</td>
                    <td>{{ implode(', ', $doc['visit_dates']) }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
