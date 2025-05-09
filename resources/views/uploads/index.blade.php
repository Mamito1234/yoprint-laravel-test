<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSV Upload System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            box-sizing: border-box;
        }

        body {
            background-color: #f3f4f6;
            padding: 40px;
        }

        h1, h2 {
            text-align: center;
            color: #1f2937;
        }

        .upload-container {
            max-width: 600px;
            margin: 0 auto 40px auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.06);
        }

        #drop-area {
            padding: 30px;
            border: 2px dashed #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            text-align: center;
            transition: 0.3s ease;
            cursor: pointer;
        }

        #drop-area.hover {
            background-color: #e0f2fe;
            border-color: #3b82f6;
        }

        .upload-container button {
            margin-top: 10px;
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .upload-container button:hover {
            background-color: #1e40af;
        }

        #file-name {
            margin-top: 10px;
            font-weight: bold;
            color: #1f2937;
            display: none;
        }

        table {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }

        th, td {
            padding: 16px;
            text-align: left;
            border: 1px solid #e5e7eb;
        }

        th {
            background-color: #f3f4f6;
            font-weight: 600;
            cursor: pointer;
            position: relative;
        }

        th .arrow {
            margin-left: 8px;
            font-size: 12px;
            color: #6b7280;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .pending { background: #fef3c7; color: #92400e; }
        .processing { background: #e0f2fe; color: #0369a1; }
        .completed { background: #dcfce7; color: #166534; }
        .failed { background: #fee2e2; color: #991b1b; }

        small {
            color: #6b7280;
        }

        .sort-indicator {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>

    <h1>Upload CSV File</h1>

    <div class="upload-container">
        <form id="uploadForm" action="{{ route('uploads.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div id="drop-area">
                <p id="drop-text"><strong>Drag & Drop your CSV here</strong> or click to browse</p>
                <p id="file-name"></p>
                <input type="file" name="csv_file" id="fileInput" style="display: none;" required>
            </div>
            <button type="submit">Upload File</button>
        </form>
    </div>

    <h2>Upload History</h2>

    <div id="upload-table">
        <table id="uploadTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Time <span class="arrow" id="arrow-0">&#9650;</span></th>
                    <th onclick="sortTable(1)">File Name <span class="arrow" id="arrow-1">&#9650;</span></th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($uploads as $upload)
                    <tr>
                        <td>
                            {{ $upload->created_at->format('Y-m-d H:i') }}<br>
                            <small>({{ $upload->created_at->diffForHumans() }})</small>
                        </td>
                        <td>{{ $upload->filename }}</td>
                        <td>
                            <span class="status {{ $upload->status }}">
                                {{ ucfirst($upload->status) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('file-name');

        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('hover');
        });

        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('hover');
        });

        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('hover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileNameDisplay.textContent = `Selected: ${files[0].name}`;
                fileNameDisplay.style.display = 'block';
            }
        });

        dropArea.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = `Selected: ${fileInput.files[0].name}`;
                fileNameDisplay.style.display = 'block';
            }
        });

        setInterval(() => {
            fetch("{{ route('uploads.index') }}")
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.querySelector('#upload-table').innerHTML;
                    document.querySelector('#upload-table').innerHTML = newTable;
                });
        }, 3000);

        let sortAsc = [true, true];

        function sortTable(columnIndex) {
            const table = document.getElementById("uploadTable");
            const rows = Array.from(table.rows).slice(1);
            const type = columnIndex === 0 ? 'date' : 'string';

            rows.sort((a, b) => {
                let valA = a.cells[columnIndex].innerText;
                let valB = b.cells[columnIndex].innerText;

                if (type === 'date') {
                    valA = new Date(valA.split('\n')[0]);
                    valB = new Date(valB.split('\n')[0]);
                } else {
                    valA = valA.toLowerCase();
                    valB = valB.toLowerCase();
                }

                return sortAsc[columnIndex] ? valA > valB ? 1 : -1 : valA < valB ? 1 : -1;
            });

            sortAsc[columnIndex] = !sortAsc[columnIndex];
            const tbody = table.querySelector("tbody");
            tbody.innerHTML = "";
            rows.forEach(row => tbody.appendChild(row));

            for (let i = 0; i <= 1; i++) {
                document.getElementById(`arrow-${i}`).innerHTML = sortAsc[i] ? '▲' : '▼';
            }
        }
    </script>
</body>
</html>
