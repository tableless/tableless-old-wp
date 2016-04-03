# Tableless
A ideia do Tableless.com.br sempre foi contribuir para que a comunidade de desenvolvimento web brasileira crescesse tanto em conhecimento técnico quanto comunidade. Contribuir codificando para projetos com código aberto, é uma das maneiras de crescer no mercado, obter conhecimento e ainda ajudar a comunidade a crescer ainda mais. 

Para tanto, disponibilizamos o código do site Tableless.com.br para que a comunidade possa contribuir com novas implementações, correção de bugs, melhoria de código e principalmente performance do site.

Se você quer contribuir, faça um fork deste projeto e submeta as suas mudanças via Pull Request. Alguns detalhes seguem abaixo:

## Wordpress e Banco de Dados
O site é baseado em Wordpress. Nós versionamos não apenas o tema do site, mas todo o Wordpress. Por isso, basta clonar o projeto.

Para que o projeto funcione, você vai precisar instalar o banco de dados onde contém os posts do site. Para tanto, [baixe o banco deste link](https://www.dropbox.com/s/z9gjeht841ns6bp/tablelessBancoDemo.sql?dl=0).

O link base do site no Banco é **http://localhost/tableless/**. Você pode mudar isso direto no banco, configurar seu hosts, sei lá. Aqui estou usando o Apache padrão do Mac, com meu **localhost** configurado para o diretório **Sites**.

Para que os posts apareçam com suas respectivas imagens, [baixe a pasta **uploads**](https://www.dropbox.com/s/19oqay8faqw6p08/uploads.tar.gz?dl=0) e descompactar dentro da pasta **wp-content**.

## O Tema
O tema fica dentro da pasta **wp-content/themes/tableless**.

Existem uma série de issues que podem ser feitas cadastradas aqui no GitHub. Se você encontrar algum problema no thema ou tiver alguma ideia, pode cadastrar nas issues.

## GULP
Usamos o Gulp como task runner e praticamente serve para processar o SASS e minificar JS. Por isso, assim que clonar o projeto, rode `npm install` direto do **raiz** do projeto.

Depois basta rodar `gulp` para que o Gulp fique vigiando os arquivos SASS e o `scripts.js`.

O CSS está baseado em SASS. Eu sei que é muito popular por aí usar a sintaxe SCSS, mas no projeto estamos usando a sintaxe SASS mesmo.