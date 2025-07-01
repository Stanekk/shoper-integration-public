import {getModalEL, toggleModal} from "./modal";
import axios from "axios";

const wholesalerAssignModalBtn = document.querySelectorAll('.wholesaler-assign-modal-btn');
const wholesalerAssignModalId = 'wholesaler-modal';

const modalClassList = {
    LOADING: 'wholesaler__assign-modal-body--loading',
}


let clickedWholesalerId = null;
let publishersUrl = null;
let wholesalersUrl = null;
let wholesalerAssignUrl = null;

let assignedPublishers = [];
let unAssignedPublishers = [];

function getEndpoints() {
    let dataUrl = document.querySelector('#url-data');
    if (dataUrl) {
        publishersUrl = dataUrl.dataset.publishersUrl
        wholesalersUrl = dataUrl.dataset.wholesalersUrl;
        wholesalerAssignUrl = dataUrl.dataset.wholesalerAssingUrl;
        console.log(dataUrl.dataset)
    } else {
        console.error('invalid data url')
    }
}


const handleWholesalerAssignModalClick = (e) => {
    toggleModal(wholesalerAssignModalId);
    clickedWholesalerId = e.target.dataset.wholesalerId;
    console.log(clickedWholesalerId);
    const modalEl = getModalEL(wholesalerAssignModalId);
    if (modalEl.classList.contains('modal--active')) {
        initializeModalData(modalEl);
    }
}

const handleSaveModal = (modalEl) => {
    const assignUrl = updateAssignUrl(clickedWholesalerId);
    let assignedPublishersIds = assignedPublishers.map((el)=>el.id);

    setModalLoading(true,modalEl);
    axios.post(assignUrl,{
        publishersIds: assignedPublishersIds
    }).then(function (response) {
        console.log(response);
        window.location.reload();
    }).catch(function (error) {
        console.log(error);
        setModalLoading(false,modalEl);
    })
}


if (wholesalerAssignModalBtn) {
    wholesalerAssignModalBtn.forEach((el) => {
        el.addEventListener('click', (e) => handleWholesalerAssignModalClick(e))
    })
}

function initializeModalData(modalEl) {
    getEndpoints();
    assignedPublishers = [];
    unAssignedPublishers = [];

    let modalSaveBtn = modalEl.querySelector('#modal-wholesaler-assign-btn');
    let modalCancelBtn = modalEl.querySelector('#modal-wholesaler-cancel-btn');

    if (modalSaveBtn) {
        const newModalSaveBtn = modalSaveBtn.cloneNode(true);
        modalSaveBtn.replaceWith(newModalSaveBtn);
        newModalSaveBtn.addEventListener('click', () => handleSaveModal(modalEl));
    }
    if (modalCancelBtn) {
        const newModalCancelBtn = modalCancelBtn.cloneNode(true);
        modalCancelBtn.replaceWith(newModalCancelBtn);
        newModalCancelBtn.addEventListener('click', () => toggleModal(wholesalerAssignModalId));
    }

    setModalLoading(true,modalEl);

    const publishersRequest = axios.get(publishersUrl);
    const wholesalerRequest = axios.get(`${wholesalersUrl}/${clickedWholesalerId}`);

    Promise.all([publishersRequest, wholesalerRequest])
        .then(([publishersResponse, wholesalerResponse]) => {
            initializeWholesalerData(wholesalerResponse, modalEl);
            preparePublishersList(publishersResponse, modalEl);

            console.log('Both requests are complete!');

            setModalLoading(false,modalEl);
        })
        .catch(error => {
            console.error('Error during requests:', error);

            setModalLoading(false,modalEl);

        });
}


function initializeWholesalerData(wholesalerResponse,modalEl) {
    const wholesalerTitle = modalEl.querySelector('#wholesaler-assign-name');
    const publishersHtmlList = modalEl.querySelector('#modal-publishers-list');

    if(wholesalerResponse.data.name) {
        wholesalerTitle.textContent = wholesalerResponse.data.name;
    }
    if(wholesalerResponse.data.publishers && Array.isArray(wholesalerResponse.data.publishers)) {
        publishersHtmlList.innerHTML = '';
        wholesalerResponse.data.publishers.forEach((publisher)=>{
            assignedPublishers.push(publisher);
        })
        renderAssignedPublishersList(modalEl);
    }
}

function renderAssignedPublishersList(modalEl) {
    const publishersHtmlList = modalEl.querySelector('#modal-publishers-list');
    publishersHtmlList.innerHTML = '';
    assignedPublishers.map((publisher)=>{
        const publisherHtmlEl = renderPublisher(publisher);
        publishersHtmlList.append(publisherHtmlEl);
    })
}

function renderUnAssignedPublishersList(modalEl) {
    const publishersHtmlList = modalEl.querySelector('.wholesaler__assign-modal-publishers-list--all');
    publishersHtmlList.innerHTML = '';
    unAssignedPublishers.map((publisher)=>{
        const publisherHtmlEl = renderPublisher(publisher);
        if(publisher.wholesalerName) {
            const publisherWholesalerEl = document.createElement('span');
            publisherWholesalerEl.classList.add('wholesaler__publisher-wholesaler-name');
            publisherWholesalerEl.textContent = publisher.wholesalerName;
            publisherHtmlEl.appendChild(publisherWholesalerEl);
        }
        publishersHtmlList.append(publisherHtmlEl);
    })
}

function handlePublisherClick(publisher) {
    const modalEl = getModalEL(wholesalerAssignModalId);
    if(!publisherIsAssigned(publisher)) {
        unAssignedPublishers = unAssignedPublishers.filter((assignedPublisher)=>assignedPublisher.id !== publisher.id);
        assignedPublishers.push(publisher);
    } else {
        assignedPublishers = assignedPublishers.filter((assignedPublisher)=>assignedPublisher.id !== publisher.id);
        unAssignedPublishers.push(publisher);
    }
    renderAssignedPublishersList(modalEl);
    renderUnAssignedPublishersList(modalEl);
}


function renderPublisher(publisher) {
    const publisherHtmlEl = document.createElement('span');
    publisherHtmlEl.classList.add('wholesaler__publisher');
    publisherHtmlEl.textContent = publisher.name;
    publisherHtmlEl.addEventListener('click',()=>handlePublisherClick(publisher))
    return publisherHtmlEl;
}

function publisherIsAssigned(publisher) {
    return assignedPublishers.some((assignedPublisher) => assignedPublisher.id === publisher.id);
}


function preparePublishersList(response, modalEl) {
    const publishersHtmlList = modalEl.querySelector('.wholesaler__assign-modal-publishers-list--all');
    if (response.data.publishers && Array.isArray(response.data.publishers)) {
        response.data.publishers.forEach((publisher) => {
            if(!publisherIsAssigned(publisher)) {
                unAssignedPublishers.push(publisher);
            }
        });
        renderUnAssignedPublishersList(modalEl);
    } else {
        const errorEl = document.createElement('p');
        errorEl.textContent = 'Failed to download list of publishers - please again later';
        errorEl.style.color = 'red';
        publishersHtmlList.append(errorEl);
    }
}

function setModalLoading(state,modalEl) {
    let modalBody = modalEl.querySelector('.wholesaler__assign-modal-body');
    if(modalBody) {
        if(state && !modalBody.classList.contains(modalClassList.LOADING)) {
            modalBody.classList.add(modalClassList.LOADING);
        } else {
            modalBody.classList.remove(modalClassList.LOADING);
        }
    }

}

function updateAssignUrl(wholesalerId) {
    return wholesalerAssignUrl.replace('/0/', `/${wholesalerId}/`);
}

