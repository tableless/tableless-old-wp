# Tableless
A ideia do [Tableless.com.br](http://tableless.com.br) sempre foi contribuir para que a comunidade de desenvolvimento web brasileira crescesse tanto em conhecimento técnico quanto como comunidade. Contribuir com projetos de código aberto, é uma das melhores maneiras de crescer profissionalmente, por que você lida com profissionais de diversos níveis, com códigos de diversos tipos e problemas muito parecidos com problemas dos projetos que você poderá participar na empresa em que trabalha.

Para tanto tentar ajudar pessoas que gostam de contribuir em projetos opensource ou até para aqueles que nunca contribuiram e gostariam de começar agora, disponibilizamos o código do site Tableless.com.br para que você possa contribuir com novas implementações, correção de bugs, melhoria de código e principalmente performance do site.

Se você quer contribuir, simplesmente clone o projeto e submeta as suas mudanças via Pull Request, em uma branch nova. Alguns detalhes seguem abaixo:

**Stack usada:**
- WordPress
- SASS (sintaxe haml)
- jQuery
- Gulp
- MySQL

## Wordpress e Banco de Dados
O site é baseado em WordPress. Nós versionamos não apenas o tema do site, mas todo o WordPress. Por isso, basta clonar o projeto.

Para que o projeto funcione, você vai precisar instalar o banco de dados onde contém os posts do site. Para tanto, [baixe o banco deste link](https://dl.dropboxusercontent.com/u/177663/tablelessBancoDemo.sql?dl=1). User: diegoeis Pass: s3nh4d3m0

O link base do site no Banco é [http://localhost/tableless/](http://localhost/tableless/). Você pode mudar isso direto no banco, configurar seu hosts ou qualquer outra maneira que encontrar. [O próprio site do WordPress dá várias opções para fazer isso](https://codex.wordpress.org/Changing_The_Site_URL). Aqui estou usando o Apache padrão do Mac, com meu **localhost** configurado para o diretório **~/Sites**.

Para que os posts apareçam com suas respectivas imagens, [baixe a pasta **uploads**](https://dl.dropboxusercontent.com/u/177663/uploads.tar.gz?dl=0) e descompactar dentro da pasta **wp-content**. O caminho final é **/wp-content/uploads/**.

## O Tema
O tema fica dentro da pasta **wp-content/themes/tableless**.

Existem algumas [issues para serem resolvidas que estão listadas aqui no GitHub](https://github.com/tableless/tableless/issues). Se você encontrar algum problema no tema ou tiver alguma ideia, pode cadastrar lá também.

## GULP
Usamos o Gulp como task runner. Praticamente serve para processar o SASS e minificar JS. Por isso, assim que clonar o projeto, rode `npm install` direto da **raiz** do projeto para instalar todos os módulos necessários.

Depois basta rodar `gulp` no terminal para que o Gulp fique vigiando os arquivos SASS e o `scripts.js`.

O CSS está baseado em SASS. Eu sei que é muito popular por aí usar a sintaxe SCSS, mas no projeto estamos usando a sintaxe SASS mesmo.
