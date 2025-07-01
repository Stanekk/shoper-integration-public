const importerFileUploadWrapper = document.querySelectorAll('.form-dashboard__upload-file-wrapper');

function handleWrapperClick(e) {
    const fileInput = e.target.closest('.form-dashboard__upload-file-wrapper').querySelector('input[type="file"]');

    if (fileInput) {
        fileInput.click();
    }
}


function updateFileName(e) {
    const fileInput = e.target;
    const fileName = fileInput.files[0]?.name || '';

    const wrapper = fileInput.closest('.form-dashboard__upload-file-wrapper');
    const fileNameSpan = wrapper.querySelector('.form-dashboard__upload-file-name');
    const fileLabel = wrapper.querySelector('.form-dashboard__upload-file-label');
    const fileIcon = wrapper.querySelector('.form-dashboard__upload-file-wrapper-icon');

    fileNameSpan.textContent = fileName;

    if(fileName === '') {
        fileLabel.style.display = 'block';
        fileIcon.classList.remove('form-dashboard__upload-file-wrapper-icon--uploaded');
    } else {
        fileLabel.style.display = 'none';
        fileIcon.classList.add('form-dashboard__upload-file-wrapper-icon--uploaded');

    }


}


if (importerFileUploadWrapper.length) {
    importerFileUploadWrapper.forEach((wrapper) => {
        wrapper.addEventListener('click', (e) => handleWrapperClick(e));

        const fileInput = wrapper.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.addEventListener('change', updateFileName);
        }
    });
}
