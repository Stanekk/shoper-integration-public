const body = document.querySelector('body');

const LOADING_CLASSES = {
    'loading': 'app-loading'
}

const actionIndicatorElements = ['importer_save','import_products'];

function initialiseActionLoading() {
    if(!body.classList.contains(LOADING_CLASSES.loading)) {
        body.classList.add(LOADING_CLASSES.loading);
    }
}

actionIndicatorElements.forEach((el)=>{
    let domEL = document.getElementById(el);
    if(domEL) {
        domEL.addEventListener('click',initialiseActionLoading)
    }
})

