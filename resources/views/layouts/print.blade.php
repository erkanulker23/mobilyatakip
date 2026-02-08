<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yazdır')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-slate-900 p-6 md:p-8">
    <div class="no-print mb-4 flex gap-2 print:hidden">
        <button onclick="window.print()" class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 font-medium">Yazdır</button>
        <button onclick="window.close()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-medium">Kapat</button>
    </div>
    @yield('content')
    <script>
        window.onload = function() {
            if (window.location.search.includes('auto=1')) window.print();
        };
    </script>
</body>
</html>
