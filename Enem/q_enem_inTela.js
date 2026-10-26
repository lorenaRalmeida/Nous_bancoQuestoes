class BancoQuestoesUI {

    constructor() {
        this.formulario = document.getElementById("formSelect");
        this.lista = document.getElementById("listaQuestoes");
        this.mensagem = document.getElementById("mensagem");

        this.formulario.addEventListener("submit", (evento) => {
            evento.preventDefault();
            this.buscarQuestoes();
        });
    }

    async buscarQuestoes() {
        const materia = document.getElementById("materia").value;
        const ano = document.getElementById("ano").value;

        const dados = new URLSearchParams({
            materia: materia,
            ano: ano
        });

        try {
            const resposta = await fetch("lerQuestoes_enem.php", {
                method: "POST",
                body: dados
            });

            if (!resposta.ok) {
                throw new Error("Não foi possível carregar as questões.");
            }

            const questoes = await resposta.json();
            this.mostrarQuestoes(questoes);

        } catch (erro) {
            this.mensagem.textContent = erro.message;
        }
    }

    mostrarQuestoes(questoes) {
        this.lista.replaceChildren();
        let i=1;

        questoes.forEach((questao) => {
            const cartao = document.createElement("article");
            cartao.classList.add("questao");

            const numeracao = document.createElement("h2");
            numeracao.textContent = i;
            cartao.appendChild(numeracao);
            i++;

            const info = document.createElement("h3");
            info.textContent = questao.title || "Sumiu";
            cartao.appendChild(info);
            
            console.log(questao.title, questao.context);
            const enunciado = questao.context || "";
            const partes = enunciado.split(/(!\[.*?\]\(.*?\))/g);
            // split() => Separa no context as partes que são textos, e as imagems que estes possuem;

            partes.forEach((parte) => {
                const resultado = parte.match(/!\[(.*?)\]\((.*?)\)/);
                
                if (resultado) {
                    const imagem = document.createElement("img");
                    imagem.alt = resultado[1];
                    imagem.src = resultado[2];
                    cartao.appendChild(imagem);
                } else {
                    const texto = document.createElement("p");
                    texto.textContent = parte;
                    cartao.appendChild(texto);
                }
            });

            const int_alternativa = document.createElement("p");
            int_alternativa.textContent = questao.alternativesIntroduction;
            cartao.appendChild(int_alternativa);

            const alternativas = questao.alternatives;

            alternativas.forEach((alternativa) => {
                const botao = document.createElement("button");
                botao.textContent = `${alternativa.letter}) ${alternativa.text || ""}`;
                if(alternativa.file != null){
                    const img_alt = document.createElement("img");
                    img_alt.alt=`imagem da alternativa ${alternativa.letter}`;
                    img_alt.src = alternativa.file;
                    botao.appendChild(img_alt);
                }
                cartao.appendChild(botao);
            });
                
            this.lista.appendChild(cartao);
        });
    }
    
}

const banco = new BancoQuestoesUI();