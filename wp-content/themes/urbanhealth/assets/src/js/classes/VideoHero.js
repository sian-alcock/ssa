const VideoHero = () => {

  const videoWrapper = document.querySelector('#js-hero-video')
  const video = document.querySelector('#js-hero-video video')
  const videoControl = document.querySelector('#js-hero-video-control')
  const videoControlIcon = document.querySelector('#js-video-control-icon')

  // Add a little fade-in on page load
  if (videoWrapper ) {
    videoWrapper.classList.add('c-hero-large__video--show')
  }


  if (videoControl) {
    //  pause video after 30s
  setTimeout(() => {
    videoControl.click()
  }, 30000)


  // stop/start video and add class to change icon
  videoControl.addEventListener('click', () => {
    if (video.paused) {
      videoControlIcon.classList.remove("c-hero-large__video-control-icon--paused")
      videoControlIcon.classList.add("c-hero-large__video-control-icon--playing")
      video.play();
    } else {
      videoControlIcon.classList.remove("c-hero-large__video-control-icon--playing")
      videoControlIcon.classList.add("c-hero-large__video-control-icon--paused")
      video.pause();
    }
  })
  }



}

export default VideoHero;
