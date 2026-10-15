# Segurança — edição pessoal 1.0.1

Desenvolvido por Júlia Gabriela Gomes da Silva.

O plugin mantém as verificações de autenticação, permissão administrativa, método HTTP e chave de sessão do pacote de origem. O conteúdo passa pela limpeza HTML do Moodle. Os uploads aceitam imagens raster com validação e regravação; consulte `classes/local/image_policy.php`.

A URL do portal aceita HTTPS sem credenciais embutidas. O campo vazio representa um portal ainda não configurado. Os links externos usam `noopener noreferrer`.

As imagens institucionais foram retiradas. Somente imagens enviadas pela administração são servidas pelo controlador autenticado; uma imagem ausente retorna erro de arquivo não encontrado.

Nesta edição foram feitas verificações estáticas e de sintaxe JavaScript. Os testes PHP/PHPUnit incluídos não foram executados neste ambiente. Valide login, bloqueio de visitantes, permissões, sessão, uploads, edição, menu e botões em uma instalação Moodle de teste.
