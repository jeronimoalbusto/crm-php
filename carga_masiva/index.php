<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carga Masiva CRM</title>
    <!-- cdn de tailwind css -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- cdn jquery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- estilos css -->
    <style>
        /* Estilo para permitir desplazamiento solo en el cuerpo de la tabla */
        #csvDataTableWrapper {
            max-height: 500px;
            overflow-y: auto;
        }

        /* Estilo para el contenedor de la tabla */
        .table-container {
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            overflow: hidden;
        }

        /* Estilo para las celdas de la tabla */
        th,
        td {
            padding: 0.5rem;
            white-space: nowrap;
            /* Evitar salto de línea */
            overflow: hidden;
            border: 1px solid #ddd;
            /* Bordes de las celdas */
            min-width: 150px;
        }

        /* Asegurar que las celdas del encabezado y del cuerpo tengan el mismo ancho */
        table {
            table-layout: fixed;
            width: 100%;
        }

        th {
            background-color: #f3f4f6;
            /* Fondo más claro para el encabezado */
        }

        td {
            background-color: #ffffff;
            /* Fondo blanco para las celdas */
        }

        /* Bordes entre las filas */
        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* Indicador de carga */
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.8);
            color: whitesmoke;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 3rem;
            font-weight: 700;
        }

        /* Oculta elementos */
        .hidden {
            visibility: hidden;
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Carga Masiva de Clientes CRM</h1>

        <!-- Formulario de carga -->
        <form id="uploadForm" class="mb-8" enctype="multipart/form-data">
            <div class="flex flex-col space-y-4">
                <!-- Nombre y Apellido -->
                <div class="flex items-center space-x-4">
                    <input type="text" name="userFirstName" placeholder="Nombre" class="px-4 py-2 border rounded" required>
                    <input type="text" name="userLastName" placeholder="Apellido" class="px-4 py-2 border rounded" required>
                </div>

                <!-- Archivo CSV -->
                <div class="flex items-center space-x-4">
                    <input type="file" name="csvFile" accept=".csv" class="px-4 py-2 border rounded" required>
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-900">Cargar CSV</button>
                </div>
            </div>
        </form>

        <!-- Tabla de visualización -->
        <div class="table-container">
            <!-- Encabezado de la tabla -->
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-2 border">Select</th>
                        <th class="px-4 py-2 border">Company Name</th>
                        <th class="px-4 py-2 border">Domain Name</th>
                        <th class="px-4 py-2 border">Email</th>
                        <th class="px-4 py-2 border">Industry</th>
                        <th class="px-4 py-2 border">Phone</th>
                        <th class="px-4 py-2 border">Tags</th>
                        <th class="px-4 py-2 border">Address</th>
                        <th class="px-4 py-2 border">City</th>
                        <th class="px-4 py-2 border">State</th>
                        <th class="px-4 py-2 border">Zip Code</th>
                        <th class="px-4 py-2 border">Owner</th>
                        <th class="px-4 py-2 border">Note</th>
                        <th class="px-4 py-2 border">Cuit PJ</th>
                        <th class="px-4 py-2 border">Contact</th>
                    </tr>
                </thead>
            </table>

            <!-- Cuerpo de la tabla con desplazamiento -->
            <div id="csvDataTableWrapper">
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200" id="csvDataTable"></tbody>
                </table>
            </div>
        </div>

        <!-- Botones de acción -->
        <div id="actionButtons" class="mt-4 space-x-4 hidden">
            <button id="selectAll" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-700">Seleccionar Todos</button>
            <button id="approveUpload" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-900">Aprobar Carga</button>
        </div>

        <!-- Modal de confirmación -->
        <div id="confirmModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg w-1/3">
                <h3 class="text-xl font-bold mb-4">&iquest; Seguro que desea aprobar la carga &quest;</h3>
                <div class="flex justify-between">
                    <button id="confirmYes" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-900">SI</button>
                    <button id="confirmNo" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-900">NO</button>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <div id="alertContainer" class="mt-4"></div>
    </div>

    <!-- Indicador de carga -->
    <div id="loading" class="hidden">Cargando, por favor espere...</div>

    <!-- scripts de animaciones y carga de tablas, y modals -->

    <script>
        let selectedRows = {};
        let csvData = [];
        let fileName = '';

        $(document).ready(function () {
            // Manejar carga de archivo
            $('#uploadForm').on('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                fileName = $('input[type="file"]').val().split('\\').pop();

                // Mostrar indicador de carga
                $('#loading').removeClass('hidden');
                $('#actionButtons').addClass('hidden');

                $.ajax({
                    url: 'process.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.error) {
                                showAlert(data.error, 'error');
                            } else {
                                csvData = data;
                                renderTable(data);
                                $('#actionButtons').removeClass('hidden'); // Mostrar botones de acción cuando la tabla se ha cargado
                            }
                        } catch (e) {
                            showAlert('Error al procesar la respuesta del servidor', 'error');
                        }
                    },
                    error: function () {
                        showAlert('Error al procesar el archivo', 'error');
                    },
                    complete: function () {
                        // Ocultar indicador de carga después de la respuesta
                        $('#loading').addClass('hidden');
                    }
                });
            });

            // Función para renderizar la tabla con los datos
            function renderTable(data) {
                const tableBody = $('#csvDataTable');
                data.forEach((row, index) => {
                    const tr = $('<tr></tr>');
                    tr.append(`<td class="px-4 py-2 border"><input type="checkbox" data-index="${index}" class="selectRow"></td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.company_name}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.domain_name}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.email}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.industry}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.phone}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.tags}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.address}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.city}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.state}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.zip_code}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.owner}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.note}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.cuit_pj}</td>`);
                    tr.append(`<td class="px-4 py-2 border">${row.contact}</td>`);
                    tableBody.append(tr);
                });

                // selecciona las filas que desea cargar
                $('.selectRow').change(function () {
                    const rowIndex = $(this).data('index');
                    if (this.checked) {
                        selectedRows[rowIndex] = true;
                    } else {
                        delete selectedRows[rowIndex];
                    }
                    updateTableSelection();
                });
            }

            // actualizar las filas seleccionadas
            function updateTableSelection() {
                $('.selectRow').each(function () {
                    const rowIndex = $(this).data('index');
                    this.checked = selectedRows[rowIndex] !== undefined;
                });
            }

            // Función para seleccionar o desmarcar todas las filas
            $('#selectAll').click(function () {
                const allSelected = Object.keys(selectedRows).length === csvData.length;
                if (allSelected) {
                    selectedRows = {}; // Desmarcar todas
                } else {
                    csvData.forEach((_, index) => {
                        selectedRows[index] = true; // Marcar todas
                    });
                }
                updateTableSelection();
            });

            // Aprobar carga de datos
            $('#approveUpload').click(function () {
                // Mostrar modal de confirmación
                $('#confirmModal').removeClass('hidden');
            });

            // Confirmar la acción en el modal
            $('#confirmYes').click(function () {
                if (Object.keys(selectedRows).length > 0) {
                    showAlert('Carga aprobada para las filas seleccionadas', 'success');
                } else {
                    showAlert('No se ha seleccionado ninguna fila para aprobar', 'error');
                }
                // Cerrar el modal
                $('#confirmModal').addClass('hidden');
            });

            // Cancelar la acción en el modal
            $('#confirmNo').click(function () {
                // Cerrar el modal
                $('#confirmModal').addClass('hidden');
            });

            // Mostrar alerta
            function showAlert(message, type) {
                const alertContainer = $('#alertContainer');
                alertContainer.empty();
                const alertClass = type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                alertContainer.append(`<div class="px-4 py-2 ${alertClass} rounded">${message}</div>`);
            }
        });
    </script>
</body>

</html>
