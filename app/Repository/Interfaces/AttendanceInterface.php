<?php

namespace App\Repository\Interfaces;

interface AttendanceInterface
{
    public function checkIn(int $userId, array $data): array;

    public function checkOut(int $userId, array $data): array;

    public function getUserAttendance($request, ?int $userId = null): array;

    public function show(int $attendanceId): array;
}