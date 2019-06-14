/**
 * Intelligently crops an image to a perfect square centered around the face. Requires 'smartcrop.js'.
 * 
 * @param {string} imageSrc the URL source of the image
 * @param {function} cb callback to be invoked after the image has finished being cropped and
 */
function crop(imageSrc, cb) {
    let image = new Image();
    image.src = imageSrc;
    image.onload = function () {
        smartcrop.crop(image, { width: 200, height: 200 }).then(res => {
            let cropped = _crop(image, res.topCrop.width, res.topCrop.height, res.topCrop.x, res.topCrop.y);
            cb(cropped);
        });
    };
}

/**
 * Automatically crops an image and returns the contents as a Bas64 encoded string
 * 
 * @see https://yellowpencil.com/blog/cropping-images-with-javascript/
 *
 * @param {Image} imgObj the image to crop
 * @param {number} newWidth the width of the new image
 * @param {number} newHeight the height of the cropped image
 * @param {number} startX the x position to start cropping from
 * @param {number} startY the y position to start cropping from
 * @returns {string} the base64 encoded cropped image
 */
function _crop(imgObj, newWidth, newHeight, startX, startY) {
    let ratio = 1;
    //set up canvas for thumbnail
    var tnCanvas = document.createElement('canvas');
    var tnCanvasContext = tnCanvas.getContext('2d');
    tnCanvas.width = newWidth;
    tnCanvas.height = newHeight;

    /* use the sourceCanvas to duplicate the entire image. 
    This step was crucial for iOS4 and under devices. */
    var bufferCanvas = document.createElement('canvas');
    var bufferContext = bufferCanvas.getContext('2d');
    bufferCanvas.width = imgObj.width;
    bufferCanvas.height = imgObj.height;
    bufferContext.drawImage(imgObj, 0, 0);

    /* now we use the drawImage method to take the pixels from our bufferCanvas and draw them into our 
    thumbnail canvas */
    tnCanvasContext.drawImage(
        bufferCanvas,
        startX,
        startY,
        newWidth * ratio,
        newHeight * ratio,
        0,
        0,
        newWidth,
        newHeight
    );
    return tnCanvas.toDataURL();
}

/**
 * Convert a base64 string in a Blob according to the data and contentType.
 * 
 * @see http://stackoverflow.com/questions/16245767/creating-a-blob-from-a-base64-string-in-javascript
 * 
 * @param b64Data {String} Pure base64 string without contentType
 * @param contentType {String} the content type of the file i.e (image/jpeg - image/png - text/plain)
 * @param sliceSize {Int} SliceSize to process the byteCharacters
 * @return Blob
 */
function b64toBlob(b64Data, contentType, sliceSize) {
    contentType = contentType || '';
    sliceSize = sliceSize || 512;

    var byteCharacters = atob(b64Data);
    var byteArrays = [];

    for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        var slice = byteCharacters.slice(offset, offset + sliceSize);

        var byteNumbers = new Array(slice.length);
        for (var i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }

        var byteArray = new Uint8Array(byteNumbers);

        byteArrays.push(byteArray);
    }

    var blob = new Blob(byteArrays, { type: contentType });
    return blob;
}