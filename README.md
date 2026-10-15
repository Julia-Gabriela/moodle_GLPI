# Suporte GLPI para Moodle

Desenvolvido por **Júlia Gabriela Gomes da Silva**.

Plugin local para Moodle 5.0+, com atalho no menu, tutorial editável e botão flutuante configurável. O portal GLPI abre em nova aba. Não realiza SSO nem integra a API do GLPI.

## Instalação

1. Copie `glpisupport` para `local/glpisupport`.
2. Acesse Administração do site → Notificações.
3. Nas configurações do plugin, informe a URL HTTPS do seu GLPI.
4. Adapte os textos e envie suas próprias imagens na edição do tutorial. As imagens ficam ocultas por padrão; habilite cada imagem após enviá-la.

O componente `local_glpisupport` é independente: não atualiza nem migra configurações ou imagens de componentes com outro nome. Sem URL válida, os botões de acesso ao GLPI não aparecem e a página informa que o portal precisa ser configurado.

## Recursos

- Acesso somente para usuários autenticados, exceto visitantes.
- Edição de configurações e tutorial restrita à administração.
- URL HTTPS validada, sem credenciais embutidas.
- Texto, cores, posição e tamanho dos botões configuráveis.
- Imagens enviadas pela administração servidas com autenticação.
- Módulos AMD de distribuição incluídos em `amd/build`.

## Versão 1.0.1

Edição pessoal com identidade técnica independente, autoria, tutorial genérico, retirada das imagens institucionais e da integração específica com outro plugin de notícias. O endereço do portal inicia vazio.

## Validação

Verificados nomes técnicos, integridade do ZIP e sintaxe JavaScript. PHP, PHPUnit e os fluxos em Moodle não foram executados neste ambiente. Edição beta até validação funcional. Consulte também `docs/seguranca.md` e `tests/security_test.php`.

Para Git, versione o conteúdo desta pasta na raiz do repositório, incluindo `amd/src` e `amd/build`.

Licença GNU GPL v3 ou posterior, conforme os cabeçalhos do código.
