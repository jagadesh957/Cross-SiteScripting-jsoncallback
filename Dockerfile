FROM amitjudge/lampimage:1.0.0
RUN apt-get update
WORKDIR /var/www/html
RUN mkdir gpEasy
COPY gpEasy /var/www/html/gpEasy
RUN chmod 777 "/var/www/html/gpEasy/data"
EXPOSE 80
CMD ["apache2ctl","-D","FOREGROUND"]
