<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta dados de CNPJ</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>



    <div class="container py-5">
        <!-- Cabeçalho -->
        <div class="text-center mb-5">
            <img src="{{ asset('img/logo.webp') }}" alt="Logo" class="img-fluid mb-3" style="max-width: 120px;">
            <h1 class="fw-bold text-dark">Consulta Pública de CNPJs</h1>
            <p class="text-muted fs-4">Gerencie e valide CNPJs de forma simples e eficiente</p>
        </div>

        <!-- Contador de CNPJs -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-10">
                <div class="alert alert-warning shadow-sm text-center rounded-pill py-3">
                    <strong>📊 <span id="pendingCount" class="fs-4">Carregando...</span> CNPJs Pendentes de
                        Validação</strong>
                </div>
            </div>
        </div>

        <!-- Painéis principais -->
        <div class="row g-4 justify-content-center">
            <!-- Upload de Arquivo -->
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm border-0 text-center h-100" style="background-color: #6CACE4; color: white;">
                    <div class="card-header text-white" style="background-color: #3B82C4;">
                        <h5 class="mb-0">📂 Upload de Arquivo</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            @csrf
                            <label for="file" class="form-label text-white">Carregue sua planilha ou CSV</label>
                            <input type="file" id="file" name="file" class="form-control mb-3" required>
                            <button type="submit" class="btn w-100 rounded-pill"
                                style="background-color: #3B82C4; color: white;">Enviar Planilha</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Validação por Data -->
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm border-0 text-center h-100"
                    style="background-color: #F9D776; color: #D97706;">
                    <div class="card-header text-white" style="background-color: #D97706;">
                        <h5 class="mb-0">📅 Validação por Data</h5>
                    </div>
                    <div class="card-body">
                        <label for="createdAfterInput" class="form-label text-dark">Selecione a data inicial</label>
                        <input type="datetime-local" id="createdAfterInput" class="form-control mb-3">
                        <button id="validateByDateButton" class="btn w-100 rounded-pill"
                            style="background-color: #D97706; color: white;">Validar CNPJs</button>
                    </div>
                </div>
            </div>

            <!-- Arquivos no Storage -->
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-sm border-0 text-center h-100"
                    style="background-color: #7FD9A8; color: #047857;">
                    <div class="card-header text-white" style="background-color: #047857;">
                        <h5 class="mb-0">📁 Arquivos no Storage</h5>
                    </div>
                    <div class="card-body">
                        <ul id="fileList" class="list-group list-group-flush mb-3">
                            <!-- Lista de arquivos será carregada dinamicamente -->
                        </ul>
                        <button id="clearPendingCnpjsButton" class="btn w-100 rounded-pill"
                            style="background-color: #047857; color: white;">🗑 Apagar todos os CNPJs</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validação Manual -->
        <div class="row mt-5 justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 text-center h-100" style="background-color: #a1a194; color: white;">
                    <div class="card-header text-white" style="background-color: #a33d24;">
                        <h5 class="mb-0">✅ Validação de CNPJs</h5>
                    </div>
                    <div class="card-body">
                        <label for="cnpjCountInput" class="form-label text-white">Digite o número de CNPJs para
                            validar</label>
                        <input type="number" id="cnpjCountInput" class="form-control mb-3" placeholder="Ex: 900">
                        <button id="calculateButton" class="btn w-100 mb-3 rounded-pill"
                            style="background-color: #24635d; color: white;">Calcular Tempo</button>
                        <div id="calculatedTime" class="alert alert-info text-center d-none">
                            Tempo estimado: <span id="calculatedTimeValue">--:--</span>
                        </div>
                        <button id="validateButton" class="btn w-100 rounded-pill"
                            style="background-color: #299745; color: white;" disabled>Validar CNPJs</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-5 text-center text-muted">
            <p class="mb-0">🚀 Desenvolvido por <strong>Vagner Giraldino</strong> - 2025 🌍</p>
            <a href="https://github.com/VagnerGiraldinoJr" target="_blank" class="text-decoration-none">
                <i class="fab fa-github"></i> GitHub
            </a>
        </footer>
    </div>




    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Elementos do DOM
            const validateButton = document.getElementById('validateButton');
            const calculateButton = document.getElementById('calculateButton');
            const cnpjCountInput = document.getElementById('cnpjCountInput');
            const calculatedTime = document.getElementById('calculatedTime');
            const calculatedTimeValue = document.getElementById('calculatedTimeValue');
            const pendingCounter = document.getElementById('pendingCounter');
            const uploadForm = document.getElementById('uploadForm');
            const fileInput = document.getElementById('file');
            const clearPendingCnpjsButton = document.getElementById('clearPendingCnpjsButton');

            // Limpar CNPJs pendentes com SweetAlert2
            if (clearPendingCnpjsButton) {
                clearPendingCnpjsButton.addEventListener('click', async () => {
                    const result = await Swal.fire({
                        title: 'Tem certeza?',
                        text: 'Todos os CNPJs pendentes serão apagados. Esta ação não pode ser desfeita.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sim, apagar!',
                        cancelButtonText: 'Cancelar',
                    });

                    if (result.isConfirmed) {
                        try {
                            const response = await fetch('{{ route('clearPendingCnpjs') }}', {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute('content'),
                                },
                            });

                            if (response.ok) {
                                const data = await response.json();

                                // SweetAlert2: Sucesso
                                Swal.fire({
                                    icon: 'success',
                                    title: 'CNPJs Apagados!',
                                    text: data.message,
                                });

                                // Atualiza o contador de pendentes
                                const pendingCount = document.getElementById('pendingCount');
                                if (pendingCount) {
                                    pendingCount.textContent = '0';
                                }
                            } else {
                                throw new Error('Erro ao apagar os CNPJs');
                            }
                        } catch (error) {
                            console.error('Erro ao apagar os CNPJs:', error);

                            // SweetAlert2: Erro
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: 'Ocorreu um erro ao apagar os CNPJs. Tente novamente.',
                            });
                        }
                    }
                });
            }

            // Upload de Arquivo com SweetAlert2
            if (uploadForm) {
                uploadForm.addEventListener('submit', async (event) => {
                    event.preventDefault(); // Evita o envio tradicional do formulário

                    const formData = new FormData();
                    formData.append('file', fileInput.files[0]);

                    try {
                        const response = await fetch('{{ route('upload') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: formData,
                        });

                        if (response.ok) {
                            const data = await response.json();

                            // SweetAlert2: Upload concluído
                            Swal.fire({
                                icon: 'success',
                                title: 'Upload Concluído!',
                                html: `
                                    <p><strong>${data.success}</strong></p>
                                    <p><strong>Total:</strong> ${data.count} registros</p>
                                    <p><strong>Inválidos:</strong> ${data.invalid}</p>
                                `,
                            });

                            // Atualiza os dados na tela
                            loadPendingCnpjsCount();
                        } else {
                            throw new Error('Erro no envio do arquivo');
                        }
                    } catch (error) {
                        console.error('Erro no upload:', error);

                        // SweetAlert2: Erro
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro no Upload',
                            text: 'Ocorreu um erro ao enviar o arquivo. Por favor, tente novamente.',
                        });
                    }
                });
            }

            if (validateByDateButton) {
                validateByDateButton.addEventListener('click', async () => {
                    const createdAfterInput = document.getElementById('createdAfterInput');
                    const createdAfter = createdAfterInput ? createdAfterInput.value : null;

                    if (!createdAfter) {
                        // Alerta SweetAlert2 se a data não foi fornecida
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atenção',
                            text: 'Por favor, informe uma data válida.',
                        });
                        return;
                    }

                    // Desabilita o botão enquanto processa
                    validateByDateButton.disabled = true;

                    try {
                        const response = await fetch('{{ route('validateCnpjsByDate') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                created_after: createdAfter,
                            }),
                        });

                        const data = await response.json();

                        // Exibe o resultado com SweetAlert2
                        Swal.fire({
                            icon: data.success ? 'success' : 'info',
                            title: data.success ? 'Validação concluída!' :
                                'Nenhum CNPJ para validar',
                            text: data.message || (data.success ?
                                'Os CNPJs foram validados com sucesso.' :
                                'Nenhum CNPJ pendente para a data fornecida.'),
                        });
                    } catch (error) {
                        console.error('Erro ao validar CNPJs por data:', error);
                        // Alerta de erro com SweetAlert2
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Ocorreu um erro ao validar os CNPJs. Tente novamente.',
                        });
                    } finally {
                        // Reativa o botão após a execução
                        validateByDateButton.disabled = false;
                    }
                });
            }

            if (uploadForm) {
                uploadForm.addEventListener('submit', async (event) => {
                    event.preventDefault(); // Evita o envio tradicional do formulário

                    const formData = new FormData();
                    formData.append('file', fileInput.files[0]);

                    // Exibe o modal de loading
                    Swal.fire({
                        title: 'Enviando...',
                        text: 'Aguarde enquanto a planilha está sendo processada.',
                        icon: 'info',
                        allowOutsideClick: false, // Impede que o usuário feche o modal
                        showConfirmButton: false, // Remove o botão de confirmação
                        didOpen: () => {
                            Swal.showLoading(); // Mostra o spinner de carregamento
                        },
                    });

                    try {
                        const response = await fetch('{{ route('upload') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: formData,
                        });

                        if (response.ok) {
                            const data = await response.json();

                            // Atualiza os dados na tela, se necessário
                            loadPendingCnpjsCount();
                        } else {
                            throw new Error('Erro no envio do arquivo');
                        }
                    } catch (error) {
                        console.error('Erro no upload:', error);

                        // Fecha o modal de loading e exibe o erro
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro no Upload',
                            text: 'Ocorreu um erro ao enviar o arquivo. Por favor, tente novamente.',
                        });
                    }
                });
            }


            // Função para carregar contagem de CNPJs pendentes
            function loadPendingCnpjsCount() {
                fetch('{{ route('getPendingCnpjsCount') }}')
                    .then(response => response.json())
                    .then(data => {
                        const pendingCount = document.getElementById('pendingCount');
                        if (pendingCount) {
                            pendingCount.textContent = data.total;
                        }
                        if (pendingCounter) {
                            pendingCounter.classList.remove('alert-warning', 'alert-success');
                            pendingCounter.classList.add(data.total > 0 ? 'alert-warning' : 'alert-success');
                        }
                    })
                    .catch(() => {
                        const pendingCount = document.getElementById('pendingCount');
                        if (pendingCount) {
                            pendingCount.textContent = 'Erro ao carregar!';
                        }
                    });
            }

            // Calcula tempo estimado com SweetAlert2
            if (calculateButton && calculatedTime && calculatedTimeValue) {
                calculateButton.addEventListener("click", () => {
                    console.log('Botão Calcular Tempo clicado');

                    // Obtém o valor digitado no input
                    const count = parseInt(cnpjCountInput?.value, 10);

                    // Verifica se o valor é válido
                    if (!count || count <= 0) {
                        Swal.fire({
                            icon: "error",
                            title: "Número inválido!",
                            text: "Por favor, insira um número válido para calcular o tempo.",
                        });
                        return;
                    }

                    // Calcula o tempo estimado com base em 3 CNPJs por minuto
                    const minutes = Math.ceil(count / 3);
                    const hours = Math.floor(minutes / 60);
                    const remainingMinutes = minutes % 60;

                    // Atualiza o elemento com o tempo calculado
                    calculatedTimeValue.textContent = `${hours}h ${remainingMinutes}m`;
                    calculatedTime.classList.remove("d-none"); // Torna o elemento visível

                    // Ativa o botão de validação
                    if (validateButton) validateButton.disabled = false;
                });
            }


            // Validação de CNPJs
            if (validateButton) {
                validateButton.addEventListener('click', () => {
                    const count = parseInt(cnpjCountInput?.value, 10);
                    if (!count || count <= 0) {
                        // SweetAlert2: Número inválido
                        Swal.fire({
                            icon: 'error',
                            title: 'Número inválido!',
                            text: 'Por favor, insira um número válido para validar os CNPJs.',
                        });
                        return;
                    }

                    validateButton.disabled = true;
                    calculateButton.disabled = true;
                    cnpjCountInput.disabled = true;

                    let processed = 0;

                    // Exibe o modal de loading antes de iniciar a validação
                    Swal.fire({
                        title: 'Validando CNPJs...',
                        text: 'Por favor, aguarde enquanto os CNPJs estão sendo validados.',
                        icon: 'info',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading(); // Exibe o spinner de carregamento
                        },
                    });


                    function processBatch() {
                        const batch = Math.min(3, count - processed);
                        if (batch <= 0) {
                            // Fecha o modal de loading ao concluir
                            Swal.close();

                            // Exibe mensagem de sucesso
                            Swal.fire({
                                icon: 'success',
                                title: 'Validação concluída!',
                                text: `${processed} CNPJs validados.`,
                            });

                            return;
                        }

                        fetch('{{ route('validateCnpjs') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    count: batch,
                                }),
                            })

                            .then(response => response.json())
                            .then(data => {
                                processed += data.processed_count;

                                if (processed < count) {
                                    // Continua o processamento em lotes
                                    setTimeout(processBatch, 60000);
                                } else {
                                    // Fecha o modal de loading ao concluir
                                    Swal.close();

                                    // Exibe mensagem de sucesso
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Validação concluída!',
                                        text: `${processed} CNPJs validados.`,
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Erro ao validar CNPJs:', error);

                                // Fecha o modal de loading e exibe mensagem de erro
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro',
                                    text: 'Erro ao processar os CNPJs. Tente novamente.',
                                });
                            })
                            .finally(() => {
                                validateButton.disabled = false;
                                calculateButton.disabled = false;
                                cnpjCountInput.disabled = false;
                            });
                    }

                    // Inicia o processo de validação em lotes
                    processBatch();
                });
            }

            // Inicializa a contagem de pendentes
            loadPendingCnpjsCount();
        });
    </script>


</body>

</html>
