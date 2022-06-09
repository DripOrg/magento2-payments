# Modulo Drip para Magento 2

## Instalação

### 1: Arquivo zipado

- Descompacte o pacote na pasta `app/code/Drip/Payments`
- Ative o modulo com o comando `php bin/magento module:enable Drip_Payments --clear-static-content`
- Faça as atualizações do banco de dados com o comando `php bin/magento setup:upgrade`
- Limpe o cache da aplicação com o comando `php bin/magento cache:clean`
- Gere um novo cache para as classes dos módulos `php bin/magento setup:di:compile`
- Gere o conteudo estatico novamente `php bin/magento setup:static-content:deploy`
- Caso existam problemas com permissão, use o comando `chmod 777 -R var/ pub/ generated/`

### 2: Via Composer

- Instale o modulo via composer com o comando `composer require drip_app/magento2-payments`
- Ative o modulo com o comando `php bin/magento module:enable Drip_Payments --clear-static-content`
- Faça as atualizações do banco de dados com o comando `php bin/magento setup:upgrade`
- Limpe o cache da aplicação com o comando `php bin/magento cache:clean`
- Gere um novo cache para as classes dos módulos `php bin/magento setup:di:compile`
- Gere o conteudo estatico novamente `php bin/magento setup:static-content:deploy`
- Caso existam problemas com permissão, use o comando `chmod 777 -R var/ pub/ generated/`

## Atualização

- Atualize o modulo drip payments: `composer require drip_app/magento2-payments:* --update-with-dependencies`
- Faça as atualizações do banco de dados com o comando `php bin/magento setup:upgrade`
- Limpe o cache da aplicação com o comando `php bin/magento cache:clean`
- Caso existam problemas com permissão, use o comando `chmod 777 -R var/ pub/ generated/`


## Para instalar a versao estavel anterior(0.0.17):
- Instale o modulo via composer com o comando `composer require drip_app/magento2-payments:0.0.17`
- Siga o guia `Instalação via composer`
