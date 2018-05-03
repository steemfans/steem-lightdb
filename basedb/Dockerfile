FROM ety001/steem-python
RUN apk add --no-cache py3-requests && pip3 install PyMySQL
COPY . /app
WORKDIR /app
CMD ["/app/run.py"]
